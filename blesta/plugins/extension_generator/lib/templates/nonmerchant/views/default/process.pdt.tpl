
	<?php
    // Disable CSRF for this form
    $this->Form->setCsrfOptions(['set_on_create' => false]);
    $this->Form->create((isset($post_to) ? $post_to : null), ['method' => (isset($form_method) ? $form_method : 'post')]);
    if ((isset($fields) ? $fields : null)) {
        foreach ($fields as $key => $value) {
            $this->Form->fieldHidden($key, $value);
        }
    }

    $this->Form->fieldImage(
        'submit',
        $this->_('{{class_name}}.buildprocess.submit', true),
        [
            'src' => '',
            'alt' => $this->_('{{class_name}}.buildprocess.submit', true),
            'title' => $this->_('{{class_name}}.buildprocess.submit', true)
        ]
    );

    $this->Form->end();
