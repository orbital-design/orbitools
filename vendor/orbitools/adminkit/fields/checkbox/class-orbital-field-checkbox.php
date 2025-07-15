<?php

/**
 * Checkbox Field Class
 *
 * Handles rendering and functionality for checkbox input fields.
 * Supports both single checkbox and multiple checkbox options.
 *
 * @package    Orbitools\AdminKit
 * @subpackage Fields
 * @since      1.0.0
 */

namespace Orbitools\AdminKit;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Checkbox field implementation
 *
 * @since 1.0.0
 */
class Field_Checkbox extends Field_Base
{

    /**
     * Render the checkbox field
     *
     * @since 1.0.0
     */
    public function render()
    {
        // Check if this is a multi-checkbox (has options)
        if (isset($this->field['options']) && is_array($this->field['options'])) {
            $this->render_multi_checkbox();
        } else {
            $this->render_single_checkbox();
        }
    }

    /**
     * Render single checkbox
     *
     * @since 1.0.0
     */
    private function render_single_checkbox()
    {
        $checked = ! empty($this->value);

        // Prepare template variables
        $template_vars = array(
            'field'      => $this->field,
            'value'      => $this->value,
            'field_id'   => $this->get_field_id(),
            'field_name' => $this->get_field_name(),
            'input_name' => $this->get_input_name(),
            'checked'    => $checked,
            'attributes' => $this->render_attributes(array(
                'value' => '1',
                'checked' => $checked
            )),
        );

        $this->render_template('single-checkbox', $template_vars);
    }

    /**
     * Render multiple checkboxes
     *
     * @since 1.0.0
     */
    private function render_multi_checkbox()
    {
        // Ensure value is an array
        $values = is_array($this->value) ? $this->value : array();

        // Prepare template variables
        $template_vars = array(
            'field'      => $this->field,
            'values'     => $values,
            'field_id'   => $this->get_field_id(),
            'field_name' => $this->get_field_name(),
            'input_name' => $this->get_input_name(),
            'options'    => $this->field['options'],
        );

        $this->render_template('multi-checkbox', $template_vars);
    }

    /**
     * Render template with variables
     *
     * @since 1.0.0
     * @param string $template_name Template name (without .php extension)
     * @param array  $template_vars Variables to extract into template scope
     */
    private function render_template($template_name, $template_vars = array())
    {
        // Check for custom template first
        if (isset($this->field['template'])) {
            $custom_template = $this->field['template'];

            // Security: Only allow files within WordPress directories
            $allowed_paths = array(
                ABSPATH,
                WP_CONTENT_DIR,
                get_template_directory(),
                get_stylesheet_directory(),
            );

            $is_allowed = false;
            $template_path = '';

            // Handle relative paths by making them relative to the plugin root directory
            if (strpos($custom_template, '/') !== 0) {
                // Get plugin root directory - go up from includes/admin-framework/fields/checkbox/ to plugin root
                $plugin_root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
                $template_path = $plugin_root . '/' . $custom_template;
            } else {
                $template_path = $custom_template;
            }

            $real_path = realpath($template_path);
            if ($real_path) {
                foreach ($allowed_paths as $allowed_path) {
                    if (strpos($real_path, realpath($allowed_path)) === 0) {
                        $is_allowed = true;
                        break;
                    }
                }
            }

            if ($is_allowed && file_exists($template_path)) {
                // Extract variables into template scope
                extract($template_vars);
                include $template_path;
                return;
            }
        }

        // Use default template
        $default_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name . '.php';

        if (file_exists($default_template)) {
            // Extract variables into template scope
            extract($template_vars);
            include $default_template;
        }
    }

    /**
     * Sanitize checkbox field value
     *
     * @since 1.0.0
     * @param mixed $value Value to sanitize.
     * @return string|array Sanitized value.
     */
    public function sanitize($value)
    {
        // Multi-checkbox (has options)
        if (isset($this->field['options']) && is_array($this->field['options'])) {
            if (! is_array($value)) {
                return array();
            }

            $sanitized = array();
            $valid_options = array_keys($this->field['options']);

            foreach ($value as $item) {
                $sanitized_item = sanitize_text_field($item);
                // Only include valid options
                if (in_array($sanitized_item, $valid_options)) {
                    $sanitized[] = $sanitized_item;
                }
            }

            return $sanitized;
        }

        // Single checkbox
        return ! empty($value) ? '1' : '';
    }

    /**
     * Validate checkbox field value
     *
     * @since 1.0.0
     * @param mixed $value Value to validate.
     * @return bool|string True if valid, error message if invalid.
     */
    public function validate($value)
    {
        // Multi-checkbox validation
        if (isset($this->field['options']) && is_array($this->field['options'])) {
            // Ensure value is an array
            if (! is_array($value)) {
                $value = array();
            }

            // Check for required field
            if (isset($this->field['required']) && $this->field['required'] && empty($value)) {
                return sprintf('At least one option must be selected for %s.', $this->get_field_name());
            }

            // Check minimum selections
            if (isset($this->field['min_selections']) && count($value) < $this->field['min_selections']) {
                return sprintf(
                    'At least %d option(s) must be selected for %s.',
                    $this->field['min_selections'],
                    $this->get_field_name()
                );
            }

            // Check maximum selections
            if (isset($this->field['max_selections']) && count($value) > $this->field['max_selections']) {
                return sprintf(
                    'No more than %d option(s) can be selected for %s.',
                    $this->field['max_selections'],
                    $this->get_field_name()
                );
            }

            // Validate that all values are in allowed options
            $valid_options = array_keys($this->field['options']);
            foreach ($value as $item) {
                if (! in_array($item, $valid_options)) {
                    return sprintf('Invalid option selected for %s field.', $this->get_field_name());
                }
            }

            return true;
        }

        // Single checkbox validation
        if (isset($this->field['required']) && $this->field['required'] && empty($value)) {
            return sprintf('The %s field must be checked.', $this->get_field_name());
        }

        return true;
    }
}