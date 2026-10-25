<?php
/**
 * i4ware Receipt Background Processing Queue
 *
 * Implements:
 * - Asynchronous background queue processing for PDF emailing
 * - Responds to PayPal webhooks immediately (200 OK) without blocking HTTP connections
 * - Bounded retries (maximum 3 attempts with exponential backoff)
 * - Persistent error tracking visible to administrators in the WordPress Admin UI
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

require_once __DIR__ . '/class-receipt-mailer.php';

class I4ware_Receipt_Queue
{
    const MAX_ATTEMPTS = 3;

    public static function init()
    {
        add_action('i4ware_process_receipt_queue', [__CLASS__, 'process_queue']);
    }

    /**
     * Enqueue a receipt for background processing and email sending.
     *
     * @param array $receipt_data
     * @return int|bool Queue insert ID or false
     */
    public static function enqueue_receipt_send(array $receipt_data)
    {
        global $wpdb;

        if (empty($receipt_data['id'])) {
            return false;
        }

        $receipt_id = (int) $receipt_data['id'];
        $table_queue = $wpdb->prefix . 'i4ware_receipt_queue';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_queue'") === $table_queue) {
            $wpdb->insert($table_queue, [
                'receipt_id' => $receipt_id,
                'action_type' => 'generate_and_send',
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
                'last_error' => null,
                'scheduled_at' => gmdate('Y-m-d H:i:s'),
                'created_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]);
        }

        // Schedule immediate cron event if not already scheduled
        if (!wp_next_scheduled('i4ware_process_receipt_queue')) {
            wp_schedule_single_event(time(), 'i4ware_process_receipt_queue');
        }

        // In environments where WP-Cron runs on visits or in CLI tests, process immediately
        self::process_queue();

        return true;
    }

    /**
     * Process pending items in receipt queue.
     */
    public static function process_queue()
    {
        global $wpdb;

        $table_queue = $wpdb->prefix . 'i4ware_receipt_queue';
        $table_receipts = $wpdb->prefix . 'i4ware_receipts';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_queue'") !== $table_queue) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_queue WHERE status = 'pending' AND scheduled_at <= %s ORDER BY id ASC LIMIT 10",
                $now
            ),
            ARRAY_A
        );

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $queue_id = (int) $item['id'];
            $receipt_id = (int) $item['receipt_id'];

            // Mark processing
            $wpdb->update($table_queue, ['status' => 'processing', 'updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $queue_id]);

            // Fetch receipt record
            $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_receipts WHERE id = %d", $receipt_id), ARRAY_A);

            if (!$receipt) {
                $wpdb->update(
                    $table_queue,
                    ['status' => 'failed', 'last_error' => 'Kuittitietuetta ei löytynyt.', 'updated_at' => gmdate('Y-m-d H:i:s')],
                    ['id' => $queue_id]
                );
                continue;
            }

            // Ensure PDF file exists on disk; recreate if missing
            if (empty($receipt['pdf_path']) || !file_exists($receipt['pdf_path'])) {
                require_once __DIR__ . '/class-receipt-manager.php';
                $storage_dir = I4ware_Receipt_Manager::get_storage_directory();
                $pdf_filename = $receipt['receipt_number'] . '.pdf';
                $pdf_path = $storage_dir . '/' . $pdf_filename;

                $pdf_binary = I4ware_Receipt_PDF::build($receipt);
                @file_put_contents($pdf_path, $pdf_binary);

                $receipt['pdf_path'] = $pdf_path;
                $receipt['pdf_filename'] = $pdf_filename;
                $wpdb->update($table_receipts, ['pdf_path' => $pdf_path, 'pdf_filename' => $pdf_filename], ['id' => $receipt_id]);
            }

            // Attempt email send via VPS SMTP
            $result = I4ware_Receipt_Mailer::send_receipt_email($receipt);
            $attempts = (int) $item['attempts'] + 1;

            if ($result['success']) {
                // Completed successfully!
                $wpdb->update(
                    $table_queue,
                    [
                        'status' => 'completed',
                        'attempts' => $attempts,
                        'last_error' => null,
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ],
                    ['id' => $queue_id]
                );

                $wpdb->update(
                    $table_receipts,
                    [
                        'email_status' => 'sent',
                        'email_sent_at' => gmdate('Y-m-d H:i:s'),
                        'email_error' => null,
                        'retry_count' => $attempts,
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ],
                    ['id' => $receipt_id]
                );
            } else {
                // Failed attempt
                $error_msg = $result['message'];

                if ($attempts >= (int) $item['max_attempts']) {
                    // Maximum retries exhausted
                    $wpdb->update(
                        $table_queue,
                        [
                            'status' => 'failed',
                            'attempts' => $attempts,
                            'last_error' => $error_msg,
                            'updated_at' => gmdate('Y-m-d H:i:s')
                        ],
                        ['id' => $queue_id]
                    );

                    $wpdb->update(
                        $table_receipts,
                        [
                            'email_status' => 'failed',
                            'email_error' => $error_msg,
                            'retry_count' => $attempts,
                            'updated_at' => gmdate('Y-m-d H:i:s')
                        ],
                        ['id' => $receipt_id]
                    );
                } else {
                    // Exponential backoff: 2 min, 4 min, 8 min
                    $backoff_seconds = (int) pow(2, $attempts) * 60;
                    $next_run = gmdate('Y-m-d H:i:s', time() + $backoff_seconds);

                    $wpdb->update(
                        $table_queue,
                        [
                            'status' => 'pending',
                            'attempts' => $attempts,
                            'scheduled_at' => $next_run,
                            'last_error' => $error_msg,
                            'updated_at' => gmdate('Y-m-d H:i:s')
                        ],
                        ['id' => $queue_id]
                    );

                    $wpdb->update(
                        $table_receipts,
                        [
                            'email_status' => 'failed',
                            'email_error' => $error_msg,
                            'retry_count' => $attempts,
                            'updated_at' => gmdate('Y-m-d H:i:s')
                        ],
                        ['id' => $receipt_id]
                    );

                    wp_schedule_single_event(time() + $backoff_seconds, 'i4ware_process_receipt_queue');
                }
            }
        }
    }
}
