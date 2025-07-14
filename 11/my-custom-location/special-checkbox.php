<?php

/**
 * Custom Checkbox Template Example
 *
 * This is an example of a custom template that users can create.
 * Copy this file to your own plugin/theme and modify as needed.
 *
 * Available variables:
 * @var array  $field       - Field configuration array
 * @var mixed  $value       - Current field value
 * @var string $field_id    - Generated field ID
 * @var string $field_name  - Field display name
 * @var string $input_name  - Input name attribute
 * @var bool   $checked     - Whether checkbox is checked
 * @var string $attributes  - Rendered input attributes
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="custom-checkbox-wrapper">
    <label for="<?php echo esc_attr($field_id); ?>" class="custom-checkbox-label">
        <input type="checkbox" <?php echo $attributes; ?>>

        <span class="custom-checkbox-text">
            <strong><?php echo esc_html($field_name); ?></strong>
            <?php if (isset($field['desc'])) : ?>
            <br><small><?php echo esc_html($field['desc']); ?></small>
            <?php endif; ?>
        </span>
    </label>
</div>

<style>
.custom-checkbox-wrapper {
    background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
    border-radius: 12px;
    padding: 16px;
    margin: 8px 0;
}

.custom-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    color: white;
}

.custom-checkbox-label input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.custom-checkbox-indicator {
    flex-shrink: 0;
    margin-top: 2px;
}

.custom-checkbox-box {
    width: 24px;
    height: 24px;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.custom-checkbox-icon {
    width: 16px;
    height: 16px;
    color: white;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.3s ease;
}

.custom-checkbox-label input[type="checkbox"]:checked+.custom-checkbox-indicator .custom-checkbox-box {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 1);
}

.custom-checkbox-label input[type="checkbox"]:checked+.custom-checkbox-indicator .custom-checkbox-icon {
    opacity: 1;
    transform: scale(1);
    color: #764ba2;
}

.custom-checkbox-text {
    flex: 1;
    line-height: 1.4;
}

.custom-checkbox-text small {
    opacity: 0.8;
    font-style: italic;
}
</style>