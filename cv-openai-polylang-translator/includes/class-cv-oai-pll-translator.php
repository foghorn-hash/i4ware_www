<?php
/**
 * Class CV_OAI_PLL_Translator
 *
 * Coordinates the full WordPress single post translation flow: content extraction,
 * chunking, sequential OpenAI API requests, translation validation, draft saving,
 * Polylang association, and transient locking.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Translator {

    /**
     * Translates a single WordPress post or page from Finnish into a target language.
     *
     * @param int    $post_id          The source Finnish post ID.
     * @param string $target_lang      The target language code (e.g. 'en', 'ar').
     * @param array  $options          Translation options (title, excerpt, content, acf, caption, alt).
     * @param bool   $overwrite_draft  Whether the user explicitly confirmed overwriting an existing draft.
     * @return int|WP_Error New or updated translation post ID on success, WP_Error on failure.
     */
    public static function translate_post($post_id, $target_lang, $options, $overwrite_draft = false) {
        $start_time = microtime(true);
        $post       = get_post($post_id);

        if (!$post) {
            return new WP_Error('invalid_post', __('Source post not found.', 'cv-openai-polylang-translator'));
        }

        // 1. Dependency checks
        if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations')) {
            return new WP_Error('polylang_missing', __('Polylang is not active.', 'cv-openai-polylang-translator'));
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        if (empty($api_key)) {
            return new WP_Error('openai_missing_key', __('OpenAI API key is missing. Please configure it in settings.', 'cv-openai-polylang-translator'));
        }

        // Check if source post is in Finnish
        $source_lang = pll_get_post_language($post_id);
        if ($source_lang !== 'fi') {
            return new WP_Error('invalid_source_lang', __('Only Finnish source content can be translated.', 'cv-openai-polylang-translator'));
        }

        // 2. Existing translation check
        $translations = pll_get_post_translations($post_id);
        $existing_id  = isset($translations[$target_lang]) ? (int) $translations[$target_lang] : 0;
        $is_update    = false;

        if ($existing_id) {
            $existing_post = get_post($existing_id);
            if ($existing_post) {
                if ($existing_post->post_status === 'publish') {
                    return new WP_Error('published_translation_exists', __('A published translation already exists for this language and cannot be automatically overwritten.', 'cv-openai-polylang-translator'));
                }

                if ($existing_post->post_status === 'draft') {
                    if (!$overwrite_draft) {
                        return new WP_Error('draft_exists_no_confirm', __('A draft translation already exists. Please confirm that you want to overwrite it.', 'cv-openai-polylang-translator'));
                    }
                    $is_update = true;
                }
            }
        }

        // 3. Acquire global translation lock
        if (!CV_OAI_PLL_Translation_Lock::acquire($post_id)) {
            return new WP_Error('locked', __('Another translation job is currently running on this site. Please wait a few minutes.', 'cv-openai-polylang-translator'));
        }

        try {
            // Get ACF fields enabled for translation
            $enabled_acf_fields = get_option('cv_oai_pll_acf_fields', []);
            if (!is_array($enabled_acf_fields)) {
                $enabled_acf_fields = [];
            }

            // 4. Extract translatable payload
            $payload = CV_OAI_PLL_Content_Extractor::extract_translatable($post, $options, $enabled_acf_fields);
            if (empty($payload)) {
                throw new Exception(__('No translatable content found for the selected options.', 'cv-openai-polylang-translator'));
            }

            // Preprocess: Replace URLs and Emails with safe HTML placeholders
            $url_map = [];
            $email_map = [];
            foreach ($payload as $key => &$item) {
                if (isset($item['content']) && is_string($item['content'])) {
                    $item['content'] = self::replace_urls_with_placeholders($item['content'], $url_map);
                    $item['content'] = self::replace_emails_with_placeholders($item['content'], $email_map);
                }
            }

            // 5. Chunk the payload for resource-safety
            $chunks = CV_OAI_PLL_Content_Extractor::chunk_payload($payload);
            $translated_results = [];
            $cooldown = (int) get_option('cv_oai_pll_cooldown', 0);

            // Get target language label (e.g. "Arabic", "English")
            $target_language_name = self::get_language_name_by_code($target_lang);

            // 6. Sequential API calls and validations
            $chunk_index = 0;
            foreach ($chunks as $chunk) {
                // Apply optional cooldown between chunks
                if ($chunk_index > 0 && $cooldown > 0) {
                    sleep($cooldown);
                }

                $chat_payload = [
                    'model'       => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => CV_OAI_PLL_OpenAI_Client::get_system_prompt($target_language_name),
                        ],
                        [
                            'role'    => 'user',
                            'content' => "Translate the following JSON object translatable fields into " . $target_language_name . ". IMPORTANT: Do not translate or transliterate trademark names, product names, or technology identifiers (such as i4ware, Atlassian, Jira, WordPress, OpenAI, Microsoft, SAP, ERP, SaaS, Polylang, ACF, Freshworks, Freshchat, Microsoft Teams). They must remain in Latin (English) characters exactly as written. Return only the translated JSON matching the structure:\n\n" . wp_json_encode($chunk),
                        ],
                    ],
                ];

                $response_content = CV_OAI_PLL_OpenAI_Client::translate_payload($chat_payload, $api_key);
                if (is_wp_error($response_content)) {
                    throw new Exception($response_content->get_error_message());
                }

                $decoded_chunk = json_decode($response_content, true);
                if (!is_array($decoded_chunk)) {
                    throw new Exception(__('Failed to parse translation chunk as JSON.', 'cv-openai-polylang-translator'));
                }

                // Validate chunk response for quality and integrity
                $validation_res = CV_OAI_PLL_Validator::validate($chunk, $decoded_chunk);
                if (is_wp_error($validation_res)) {
                    throw new Exception(sprintf(__('Validation failed on chunk %d: %s', 'cv-openai-polylang-translator'), $chunk_index + 1, $validation_res->get_error_message()));
                }

                // Merge successfully translated keys
                $translated_results = array_merge($translated_results, $decoded_chunk);
                $chunk_index++;
            }

            // Restore placeholders in translated_results
            foreach ($translated_results as $key => &$val) {
                if (is_string($val)) {
                    // Restore URLs
                    foreach ($url_map as $placeholder => $url) {
                        $val = str_replace($placeholder, $url, $val);
                    }
                    // Restore Emails
                    foreach ($email_map as $placeholder => $email) {
                        $val = str_replace($placeholder, $email, $val);
                    }
                }
            }

            // 7. Compile translated elements
            $compiled = CV_OAI_PLL_Content_Extractor::compile_translated_post($post, $translated_results, $options, $enabled_acf_fields);

            // 8. Create or Update Translation Draft
            $post_data = [
                'post_type'    => $post->post_type,
                'post_status'  => 'draft', // Always draft!
                'post_title'   => $compiled['post_title'],
                'post_excerpt' => $compiled['post_excerpt'],
                'post_content' => $compiled['post_content'],
                'menu_order'   => (int) $post->menu_order,
            ];

            if ($is_update) {
                $post_data['ID'] = $existing_id;
                $new_post_id = wp_update_post($post_data, true);
            } else {
                $new_post_id = wp_insert_post($post_data, true);
            }

            if (is_wp_error($new_post_id) || !$new_post_id) {
                $err_msg = is_wp_error($new_post_id) ? $new_post_id->get_error_message() : __('Failed to insert/update post in DB.', 'cv-openai-polylang-translator');
                throw new Exception($err_msg);
            }

            // 9. Attach featured image (Original ID, do not duplicate/download)
            if (has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                if ($thumbnail_id) {
                    set_post_thumbnail($new_post_id, $thumbnail_id);
                }
            }

            // 10. Update ACF Field Values using update_field() to preserve references
            if (!empty($options['acf']) && !empty($compiled['acf_data']) && function_exists('update_field')) {
                foreach ($compiled['acf_data'] as $field_name => $field_val) {
                    update_field($field_name, $field_val, $new_post_id);
                }
            }

            // 11. Associate language and link Polylang translations
            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($new_post_id, $target_lang);
            }

            $translations = pll_get_post_translations($post_id);
            $translations[$target_lang] = $new_post_id;
            if (function_exists('pll_save_post_translations')) {
                pll_save_post_translations($translations);
            }

            // Synchronize and translate post categories and tags
            self::sync_post_taxonomies($post_id, $new_post_id, $target_lang, $api_key, $model);

            // 12. Save post meta details
            update_post_meta($new_post_id, '_cv_oai_source_post_id', (int) $post_id);
            update_post_meta($new_post_id, '_cv_oai_source_language', sanitize_text_field($source_lang));
            update_post_meta($new_post_id, '_cv_oai_target_language', sanitize_text_field($target_lang));
            update_post_meta($new_post_id, '_cv_oai_translation_date', current_time('mysql'));
            update_post_meta($new_post_id, '_cv_oai_model', sanitize_text_field($model));
            // Always set human review required to 1
            update_post_meta($new_post_id, '_cv_oai_review_required', '1');

            // 13. Log success and release lock
            CV_OAI_PLL_Logger::log($post_id, $target_lang, $model, true, '', count($payload), $new_post_id, $start_time);
            CV_OAI_PLL_Translation_Lock::release();

            return $new_post_id;

        } catch (Exception $e) {
            // Log failure and release lock
            CV_OAI_PLL_Logger::log($post_id, $target_lang, $model, false, $e->getMessage(), 0, 0, $start_time);
            CV_OAI_PLL_Translation_Lock::release();
            return new WP_Error('translation_processing_error', $e->getMessage());
        }
    }

    /**
     * Translates a Polylang language code to a readable name for the OpenAI prompt.
     */
    private static function get_language_name_by_code($code) {
        $langs = [
            'en' => 'English',
            'ar' => 'Arabic',
        ];
        if (isset($langs[$code])) {
            return $langs[$code];
        }

        if (function_exists('pll_languages_list')) {
            // Retrieve languages list from Polylang
            $pll_langs = pll_languages_list(['fields' => null]);
            if (is_array($pll_langs)) {
                foreach ($pll_langs as $l) {
                    if ($l->slug === $code) {
                        return $l->name;
                    }
                }
            }
        }

        return ucfirst($code);
    }

    /**
     * Replaces URLs in text with placeholders.
     */
    public static function replace_urls_with_placeholders($text, &$url_map) {
        $pattern = '/https?:\/\/[^\s\'"<>\(\)]+/i';
        return preg_replace_callback($pattern, function($matches) use (&$url_map) {
            $url = $matches[0];
            $clean_url = rtrim($url, '.,;:!?');
            $punctuation = substr($url, strlen($clean_url));
            
            $placeholder = array_search($clean_url, $url_map, true);
            if ($placeholder === false) {
                $index = count($url_map);
                $placeholder = 'URLPLACEHOLDER_' . $index;
                $url_map[$placeholder] = $clean_url;
            }
            return $placeholder . $punctuation;
        }, $text);
    }

    /**
     * Replaces emails in text with placeholders.
     */
    public static function replace_emails_with_placeholders($text, &$email_map) {
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i';
        return preg_replace_callback($pattern, function($matches) use (&$email_map) {
            $email = $matches[0];
            $placeholder = array_search($email, $email_map, true);
            if ($placeholder === false) {
                $index = count($email_map);
                $placeholder = 'EMAILPLACEHOLDER_' . $index;
                $email_map[$placeholder] = $email;
            }
            return $placeholder;
        }, $text);
    }

    /**
     * Synchronizes and translates categories and tags from the source post to the translated post.
     */
    private static function sync_post_taxonomies($source_post_id, $translated_post_id, $target_lang, $api_key, $model) {
        $taxonomies = ['category', 'post_tag'];
        
        foreach ($taxonomies as $taxonomy) {
            $source_terms = wp_get_object_terms($source_post_id, $taxonomy);
            if (is_wp_error($source_terms) || empty($source_terms)) {
                continue;
            }
            
            $target_term_ids = [];
            
            foreach ($source_terms as $source_term) {
                // Check if a translation already exists in Polylang
                $translated_term_id = 0;
                if (function_exists('pll_get_term')) {
                    $translated_term_id = pll_get_term($source_term->term_id, $target_lang);
                }
                
                if ($translated_term_id) {
                    $target_term_ids[] = (int) $translated_term_id;
                } else {
                    // Translate the term using OpenAI
                    $term_name = $source_term->name;
                    $target_language_name = self::get_language_name_by_code($target_lang);
                    
                    $prompt = [
                        'model'       => $model,
                        'temperature' => 0.1,
                        'messages'    => [
                            [
                                'role'    => 'system',
                                'content' => sprintf("You are a professional localization translator. Translate the following WordPress %s name from Finnish to %s. Return only the translated name, nothing else.", $taxonomy, $target_language_name),
                            ],
                            [
                                'role'    => 'user',
                                'content' => $term_name,
                            ]
                        ]
                    ];
                    
                    $translated_name = CV_OAI_PLL_OpenAI_Client::translate_payload($prompt, $api_key);
                    if (!is_wp_error($translated_name)) {
                        $translated_name = trim(strip_tags($translated_name));
                        // Create the new term in the target language
                        $new_term = wp_insert_term($translated_name, $taxonomy);
                        if (!is_wp_error($new_term) && isset($new_term['term_id'])) {
                            $new_term_id = (int) $new_term['term_id'];
                            
                            // Link term in Polylang
                            if (function_exists('pll_set_term_language')) {
                                pll_set_term_language($new_term_id, $target_lang);
                            }
                            if (function_exists('pll_save_term_translations') && function_exists('pll_get_term')) {
                                $source_lang = 'fi'; // Source is always Finnish
                                $translations = [
                                    $source_lang => $source_term->term_id,
                                    $target_lang => $new_term_id,
                                ];
                                // Add other language links if available
                                if (function_exists('pll_languages_list')) {
                                    $langs = pll_languages_list();
                                    foreach ($langs as $l) {
                                        if ($l !== $source_lang && $l !== $target_lang) {
                                            $existing_lang_term = pll_get_term($source_term->term_id, $l);
                                            if ($existing_lang_term) {
                                                $translations[$l] = $existing_lang_term;
                                            }
                                        }
                                    }
                                }
                                pll_save_term_translations($translations);
                            }
                            
                            $target_term_ids[] = $new_term_id;
                        }
                    }
                }
            }
            
            if (!empty($target_term_ids)) {
                wp_set_object_terms($translated_post_id, $target_term_ids, $taxonomy);
            }
        }
    }
}
