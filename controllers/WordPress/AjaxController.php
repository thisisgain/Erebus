<?php

namespace Origin\WordPress;

/**
 * Class AjaxController
 */
class AjaxController
{

    public function __construct()
    {
        $this->registerAction('ajax_function', 'ajaxFunction');
    }

    private function registerAction($name, $method)
    {
        add_action("wp_ajax_{$name}", [$this, $method]);
        add_action("wp_ajax_nopriv_{$name}", [$this, $method]);
    }

    private function middleware()
    {
        if (!wp_verify_nonce($_POST['security'])) {
            wp_send_json_error([
                'message' => 'Forbidden'
            ], 403);
        }
    }

    public function ajaxFunction()
    {
        $this->middleware();
        $results = [];

        wp_send_json_success([
            'results' => $results,
        ]);

    }


}
