<?php
/**
 * Class CV_OAI_PLL_Queue
 *
 * Handles populating the translation queue with Posts, Terms, and Polylang Strings,
 * and processes them in batches (including batching multiple strings into a single OpenAI API call).
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Queue {

    /**
     * Scans the website for Finnish content and populates the translation queue.
     *
     * @param string $target_lang The target language code (e.g. 'en', 'ar').
     * @return int Number of items added to the queue.
     */
    public static function populate_queue($target_lang) {
        if (!function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
            return 0;
        }

        $added_count = 0;

        // 1. Scan Posts, Pages, and CPTs
        $post_types = get_option('cv_oai_pll_post_types', ['post', 'page']);
        if (!is_array($post_types)) {
            $post_types = ['post', 'page'];
        }

        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => ['publish', 'draft', 'private', 'pending'],
            ]);

            foreach ($posts as $post) {
                $lang = pll_get_post_language($post->ID);
                if ($lang === 'fi') {
                    // Check if translation already exists and is published (we do not overwrite published ones)
                    $translations = pll_get_post_translations($post->ID);
                    $existing_id = isset($translations[$target_lang]) ? (int) $translations[$target_lang] : 0;
                    if ($existing_id) {
                        $existing_post = get_post($existing_id);
                        if ($existing_post && $existing_post->post_status === 'publish') {
                            continue; // Skip published translations
                        }
                    }

                    $queue_id = CV_OAI_PLL_DB::add_to_queue('post', $post->ID, $target_lang);
                    if ($queue_id) {
                        $added_count++;
                    }
                }
            }
        }

        // 2. Scan Taxonomies (Categories, Tags, etc.)
        $taxonomies = get_taxonomies(['public' => true]);
        foreach ($taxonomies as $taxonomy) {
            // Check if Polylang manages this taxonomy
            if (function_exists('pll_is_translated_taxonomy') && !pll_is_translated_taxonomy($taxonomy)) {
                continue;
            }

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);

            if (is_array($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if (function_exists('pll_get_term_language')) {
                        $lang = pll_get_term_language($term->term_id);
                        if ($lang === 'fi') {
                            $queue_id = CV_OAI_PLL_DB::add_to_queue('term', $term->term_id, $target_lang);
                            if ($queue_id) {
                                $added_count++;
                            }
                        }
                    }
                }
            }
        }

        // 3. Scan registered Polylang strings
        $added_count += self::populate_missing_strings($target_lang);

        // 4. Scan Menus
        $menus = wp_get_nav_menus();
        if (is_array($menus)) {
            foreach ($menus as $menu) {
                if (function_exists('pll_get_term_language')) {
                    $lang = pll_get_term_language($menu->term_id);
                    if ($lang === 'fi') {
                        $queue_id = CV_OAI_PLL_DB::add_to_queue('menu', $menu->term_id, $target_lang);
                        if ($queue_id) {
                            $added_count++;
                        }
                    }
                }
            }
        }

        return $added_count;
    }

    /**
     * Scans registered Polylang strings and adds untranslated strings to the queue.
     *
     * @param string $target_lang Target language code.
     * @return int Number of strings added.
     */
    public static function populate_missing_strings($target_lang) {
        global $polylang;
        if (!isset($polylang) || !isset($polylang->model)) {
            return 0;
        }

        $added_count = 0;
        
        $registered_strings = [];
        if (isset($polylang->strings) && method_exists($polylang->strings, 'get_strings')) {
            $registered_strings = $polylang->strings->get_strings();
        } elseif (isset($polylang->model->strings) && method_exists($polylang->model->strings, 'get_strings')) {
            $registered_strings = $polylang->model->strings->get_strings();
        } elseif (isset($polylang->model) && method_exists($polylang->model, 'get_strings')) {
            $registered_strings = $polylang->model->get_strings();
        }

        if (empty($registered_strings)) {
            return 0;
        }

        // Load the translation MO file for the target language to check translations
        require_once dirname(__FILE__) . '/class-cv-oai-pll-string-translator.php';
        $mo = CV_OAI_PLL_String_Translator::get_mo_for_language($target_lang);
        if (is_wp_error($mo)) {
            return 0;
        }

        foreach ($registered_strings as $string) {
            $name    = $string['name'];
            $context = $string['context'];
            $text    = $string['string'];

            if (empty($text)) {
                continue;
            }

            // Check if translation exists in the target MO object
            $translation = $mo->translate($text);
            
            // If the translation is identical to the source, and the target language is not fi,
            // or the translation is empty, consider it untranslated.
            // Note: translate() returns the original string if no translation is found.
            $is_translated = true;
            if ($translation === $text && $target_lang !== 'fi') {
                $is_translated = false;
            } elseif (empty($translation)) {
                $is_translated = false;
            }

            if (!$is_translated) {
                // Unique item ID for strings: md5(context ||| name)
                $item_id = md5($context . '|||' . $name);
                $queue_id = CV_OAI_PLL_DB::add_to_queue('string', $item_id, $target_lang);
                if ($queue_id) {
                    $added_count++;
                }
            }
        }

        return $added_count;
    }

    /**
     * Processes a batch of up to 10 pending translation queue items.
     *
     * @param int $batch_size Batch size limit (default 10).
     * @return array Summary of processed items.
     */
    public static function process_batch($batch_size = 10) {
        $pending_items = CV_OAI_PLL_DB::get_pending_batch($batch_size);
        $results = [
            'total'     => count($pending_items),
            'success'   => 0,
            'failed'    => 0,
            'details'   => []
        ];

        if (empty($pending_items)) {
            return $results;
        }

        // We can group strings in the batch to translate them all in one OpenAI call
        $string_items = [];
        $other_items = [];

        foreach ($pending_items as $item) {
            if ($item->item_type === 'string') {
                $string_items[] = $item;
            } else {
                $other_items[] = $item;
            }
        }

        // Set status to processing for all items in batch
        foreach ($pending_items as $item) {
            CV_OAI_PLL_DB::update_item_status($item->id, 'processing', '', $item->attempts + 1);
        }

        // 1. Process String Items (Optimized Batching!)
        if (!empty($string_items)) {
            // Group by target language code (though usually they are all the same target language in a cron run)
            $strings_by_lang = [];
            foreach ($string_items as $item) {
                $strings_by_lang[$item->target_language][] = $item;
            }

            foreach ($strings_by_lang as $lang => $items) {
                $batch_results = self::process_string_batch($items, $lang);
                $results['success'] += $batch_results['success'];
                $results['failed']  += $batch_results['failed'];
                $results['details'] = array_merge($results['details'], $batch_results['details']);
            }
        }

        // 2. Process Other Items (Posts, Terms) sequentially
        if (!empty($other_items)) {
            $options = [
                'title'   => true,
                'excerpt' => true,
                'content' => true,
                'acf'     => true,
                'caption' => true,
                'alt'     => true,
            ];

            foreach ($other_items as $item) {
                $item_id = $item->item_id;
                $target_lang = $item->target_language;

                if ($item->item_type === 'post') {
                    // Lock check: translate_post handles lock internally, but we can verify here
                    $post_id = (int) $item_id;
                    $translate_res = CV_OAI_PLL_Translator::translate_post($post_id, $target_lang, $options, true);

                    if (is_wp_error($translate_res)) {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                        $results['failed']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'post',
                            'item_id' => $post_id,
                            'success' => false,
                            'error'   => $translate_res->get_error_message()
                        ];
                    } else {
                        // Success! Retrieve token usage from last logs
                        $usage = self::get_last_logged_usage($post_id, $target_lang);
                        CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $usage);
                        $results['success']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'post',
                            'item_id' => $post_id,
                            'success' => true,
                            'draft_id'=> $translate_res
                        ];
                    }
                } elseif ($item->item_type === 'term') {
                    $term_id = (int) $item_id;
                    $translate_res = CV_OAI_PLL_Translator::translate_term_by_id($term_id, $target_lang);

                    if (is_wp_error($translate_res)) {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                        $results['failed']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'term',
                            'item_id' => $term_id,
                            'success' => false,
                            'error'   => $translate_res->get_error_message()
                        ];
                    } else {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $translate_res['usage']);
                        $results['success']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'term',
                            'item_id' => $term_id,
                            'success' => true
                        ];
                    }
                } elseif ($item->item_type === 'menu') {
                    $menu_id = (int) $item_id;
                    $translate_res = CV_OAI_PLL_Translator::translate_menu($menu_id, $target_lang);

                    if (is_wp_error($translate_res)) {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                        $results['failed']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'menu',
                            'item_id' => $menu_id,
                            'success' => false,
                            'error'   => $translate_res->get_error_message()
                        ];
                    } else {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, [
                            'model'             => get_option('cv_oai_pll_model', 'gpt-4o-mini'),
                            'prompt_tokens'     => 0,
                            'completion_tokens' => 0
                        ]);
                        $results['success']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'menu',
                            'item_id' => $menu_id,
                            'success' => true
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Processes a batch of Polylang strings.
     * Combines all strings into a single JSON object for translation.
     *
     * @param array  $items       List of queue string items.
     * @param string $target_lang Target language code.
     * @return array Progress stats.
     */
    private static function process_string_batch($items, $target_lang) {
        global $polylang;
        $results = [
            'success' => 0,
            'failed'  => 0,
            'details' => []
        ];

        if (empty($items) || !isset($polylang) || !isset($polylang->model)) {
            return $results;
        }

        require_once dirname(__FILE__) . '/class-cv-oai-pll-string-translator.php';

        // 1. Map registered strings by item_id (MD5 hash of context + name)
        $registered_strings = $polylang->model->get_strings();
        $string_map = [];
        foreach ($registered_strings as $string) {
            $hash = md5($string['context'] . '|||' . $string['name']);
            $string_map[$hash] = $string;
        }

        // 2. Prepare payload of strings that need translation
        $payload_to_translate = [];
        $item_mapping = [];

        foreach ($items as $item) {
            $hash = $item->item_id;
            if (isset($string_map[$hash])) {
                $string_info = $string_map[$hash];
                // Check Cache/Translation Memory first!
                $cached = CV_OAI_PLL_DB::get_cached_translation($string_info['string'], $target_lang);

                if ($cached !== false) {
                    // Instant success from cache!
                    $saved = CV_OAI_PLL_String_Translator::save_single_translation(
                        $string_info['string'],
                        $cached,
                        $target_lang
                    );

                    if ($saved) {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, [
                            'model'             => 'Translation Memory',
                            'prompt_tokens'     => 0,
                            'completion_tokens' => 0
                        ]);
                        $results['success']++;
                        $results['details'][] = [
                            'id'      => $item->id,
                            'type'    => 'string',
                            'item_id' => $hash,
                            'success' => true,
                            'cached'  => true
                        ];
                    } else {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'failed', __('Failed to write translation memory to database.', 'cv-openai-polylang-translator'));
                        $results['failed']++;
                    }
                } else {
                    // Not cached, translate using OpenAI
                    $payload_to_translate[$hash] = $string_info['string'];
                    $item_mapping[$hash] = $item;
                }
            } else {
                // Registered string no longer exists in Polylang!
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', __('Registered Polylang string not found.', 'cv-openai-polylang-translator'));
                $results['failed']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => false,
                    'error'   => __('Registered Polylang string not found.', 'cv-openai-polylang-translator')
                ];
            }
        }

        if (empty($payload_to_translate)) {
            return $results;
        }

        // 3. Request translation from OpenAI
        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        
        $target_language_name = CV_OAI_PLL_Translator::get_language_name_by_code($target_lang);

        $prompt = [
            'model'       => $model,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => CV_OAI_PLL_OpenAI_Client::get_system_prompt($target_language_name),
                ],
                [
                    'role'    => 'user',
                    'content' => "Translate the following JSON object containing WordPress string names and their text values from Finnish into " . $target_language_name . ". IMPORTANT: Preserve trademark brand names (i4ware, Atlassian, Jira, WordPress, OpenAI, Microsoft, SAP, ERP, SaaS, Polylang, ACF, Freshworks, Freshchat, Microsoft Teams), sprintf placeholders (%s, %d, %1\$s...), HTML tags, shortcodes, and emojis exactly. Return only the translated JSON matching the input keys:\n\n" . wp_json_encode($payload_to_translate),
                ]
            ]
        ];

        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($prompt, $api_key);

        if (is_wp_error($response)) {
            // Entire batch failed API request
            $err_msg = $response->get_error_message();
            foreach ($item_mapping as $hash => $item) {
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => false,
                    'error'   => $err_msg
                ];
            }
            return $results;
        }

        // 4. Parse and validate JSON response
        $translated_data = json_decode($response['content'], true);
        if (!is_array($translated_data)) {
            $err_msg = __('Invalid JSON structure returned by OpenAI.', 'cv-openai-polylang-translator');
            foreach ($item_mapping as $hash => $item) {
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => false,
                    'error'   => $err_msg
                ];
            }
            return $results;
        }

        // Validate individual string translations
        $validation_errs = [];
        foreach ($payload_to_translate as $hash => $source_text) {
            if (!isset($translated_data[$hash])) {
                $validation_errs[$hash] = __('Missing translated key in OpenAI response.', 'cv-openai-polylang-translator');
                continue;
            }

            // Quick validation logic matching validator rules
            $trans_val = $translated_data[$hash];
            $validation_res = CV_OAI_PLL_Validator::validate([$hash => $source_text], [$hash => $trans_val]);
            if (is_wp_error($validation_res)) {
                $validation_errs[$hash] = $validation_res->get_error_message();
            }
        }

        // Save translations and update statuses
        $token_usage = $response['usage'];
        $num_items = count($payload_to_translate);
        
        // Distribute token usage proportionally among translated items
        $avg_usage = [
            'model'             => $model,
            'prompt_tokens'     => isset($token_usage['prompt_tokens']) ? round($token_usage['prompt_tokens'] / $num_items) : 0,
            'completion_tokens' => isset($token_usage['completion_tokens']) ? round($token_usage['completion_tokens'] / $num_items) : 0
        ];

        foreach ($payload_to_translate as $hash => $source_text) {
            $item = $item_mapping[$hash];

            if (isset($validation_errs[$hash])) {
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $validation_errs[$hash]);
                $results['failed']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => false,
                    'error'   => $validation_errs[$hash]
                ];
                continue;
            }

            $translated_text = $translated_data[$hash];
            
            // Save string translation to Polylang
            $saved = CV_OAI_PLL_String_Translator::save_single_translation($source_text, $translated_text, $target_lang);

            if ($saved) {
                // Save to Cache/Translation memory
                CV_OAI_PLL_DB::add_cached_translation($source_text, $target_lang, $translated_text);

                CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $avg_usage);
                $results['success']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => true
                ];
            } else {
                $err_msg = __('Failed to write translation to database.', 'cv-openai-polylang-translator');
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'      => $item->id,
                    'type'    => 'string',
                    'item_id' => $hash,
                    'success' => false,
                    'error'   => $err_msg
                ];
            }
        }

        return $results;
    }

    /**
     * Helper: Helper to query token usage from the logger history of a post.
     * Falls back to zero if not logged.
     */
    private static function get_last_logged_usage($post_id, $target_lang) {
        $history = CV_OAI_PLL_Logger::get_post_history($post_id);
        $model = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        
        // Check if there is usage logged in transient or logger (we'll implement this link shortly)
        $usage = get_transient('cv_oai_pll_last_tokens_usage');
        if (is_array($usage) && isset($usage['post_id']) && (int) $usage['post_id'] === (int) $post_id) {
            delete_transient('cv_oai_pll_last_tokens_usage');
            return [
                'model'             => $usage['model'],
                'prompt_tokens'     => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
            ];
        }

        return [
            'model'             => $model,
            'prompt_tokens'     => 0,
            'completion_tokens' => 0
        ];
    }
}
