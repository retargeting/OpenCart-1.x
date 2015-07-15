<?php
/*
 * ADMIN CONTROLLER FILE
 * admin/controller/module
 * retargeting.php
 *
 * MODULE: Retargeting
 */

class ControllerModuleRetargeting extends Controller {

    private $error = array();

    public function index() {

        /* Load the module */
        $this->language->load('module/retargeting');
        /* Get the translated title */
        $this->document->setTitle($this->language->get('heading_title'));
        /* Load the Settings Model */
        $this->load->model('setting/setting');
        /* Load the Design Model */
        $this->load->model('design/layout');

        /*
         * Listen for $_POST and validate incoming data
         */
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            /* Save incoming data in the DB via the Settings Model */
            $this->model_setting_setting->editSetting('retargeting', $this->request->post);
            /* Feedback on save */
            $this->session->data['success'] = $this->language->get('text_success');
            /* Redirect back to the modules list */
            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

        /*
         * Get the language
         */
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['api_key'] = $this->language->get('api_key');
        $this->data['api_secret'] = $this->language->get('api_secret');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['text_content_top'] = $this->language->get('text_content_top');
        $this->data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $this->data['text_column_left'] = $this->language->get('text_column_left');
        $this->data['text_column_right'] = $this->language->get('text_column_right');

        $this->data['entry_layout'] = $this->language->get('entry_layout');
        $this->data['entry_position'] = $this->language->get('entry_position');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_code'] = $this->language->get('entry_code');

        $this->data['button_remove'] = $this->language->get('button_remove');
        $this->data['button_add_module'] = $this->language->get('button_add_module');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');

        /*
         * If any Warning gets encountered, spill it the language of choice
         */

        /* This Block returns the warning if any */
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        /* This Block returns the error code if any */
        if (isset($this->error['code'])) {
            $this->data['error_code'] = $this->error['code'];
        } else {
            $this->data['error_code'] = '';
        }

        /*
         * Nice to have some breadcrumbs in the admin area
         */
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('module/retargeting', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        /*
         * Give life to the Save and Cancel buttons
         */
        $this->data['action'] = $this->url->link('module/retargeting', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

        /*
         * If we saved have data, populate the fields
         */
        if (isset($this->request->post['api_key_field']) && isset($this->request->post['api_secret_field'])) {
            $this->data['api_key_field'] = $this->request->post['api_key_field'];
            $this->data['api_secret_field'] = $this->request->post['api_secret_field'];
            $this->data['retargeting_setEmail'] = $this->request->post['retargeting_setEmail'];
            $this->data['retargeting_addToCart'] = $this->request->post['retargeting_addToCart'];
            $this->data['retargeting_clickImage'] = $this->request->post['retargeting_clickImage'];
            $this->data['retargeting_commentOnProduct'] = $this->request->post['retargeting_commentOnProduct'];
            $this->data['retargeting_mouseOverPrice'] = $this->request->post['retargeting_mouseOverPrice'];
            $this->data['retargeting_setVariation'] = $this->request->post['retargeting_setVariation'];
        } else {
            $this->data['api_key_field'] = $this->config->get('api_key_field');
            $this->data['api_secret_field'] = $this->config->get('api_secret_field');
            $this->data['retargeting_setEmail'] = $this->config->get('retargeting_setEmail');
            $this->data['retargeting_addToCart'] = $this->config->get('retargeting_addToCart');
            $this->data['retargeting_clickImage'] = $this->config->get('retargeting_clickImage');
            $this->data['retargeting_commentOnProduct'] = $this->config->get('retargeting_commentOnProduct');
            $this->data['retargeting_mouseOverPrice'] = $this->config->get('retargeting_mouseOverPrice');
            $this->data['retargeting_setVariation'] = $this->config->get('retargeting_setVariation');
        }

        /*
         * Get the base URL for our shop
         */
        $this->data['shop_url'] = HTTP_CATALOG;

        /* Avoid any notices */
        $this->data['modules'] = array();

        /* Parses the Module Settings such as Layout, Position,Status & Order Status to the view */
        if (isset($this->request->post['retargeting_module'])) {
            $this->data['modules'] = $this->request->post['retargeting_module'];
        } elseif ($this->config->get('retargeting_module')) {
            $this->data['modules'] = $this->config->get('retargeting_module');
        }

        // Loading the Design Layout Models
        $this->load->model('design/layout');
        // Getting all the Layouts available on system
        $this->data['layouts'] = $this->model_design_layout->getLayouts();

        /* Load the retargeting template */
        $this->template = 'module/retargeting.tpl';
        /* Adding header and footer to our default template */
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render()); // Rendering the Output

    }

    /* Function that validates the data when Save Button is pressed */
    protected function validate() {

        /* Check the user permission to manipulate the module*/
        if (!$this->user->hasPermission('modify', 'module/retargeting')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        /* Check if the api_*_field is properly set to save into database, otherwise the error is returned*/
        if (!$this->request->post['api_key_field'] || !$this->request->post['api_secret_field']) {
            $this->error['code'] = $this->language->get('error_code');
        }

        /* Returns true if no error is found, else false if any error detected */
        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Installation triggered
     */
    public function install() {

        // Load models & lang files
        $this->load->model('design/layout');
        $this->load->model('setting/setting');

        $settings = array();

        // Add our module on every possible layout, custom or standard
        foreach ($this->model_design_layout->getLayouts() as $layout) {
            $settings['retargeting_module'][] = array(
                'layout_id' => $layout['layout_id'],
                'position' => 'content_bottom',
                'status' => '1',
                'sort_order' => '99',
            );
        }

        // Save the settings
        $this->model_setting_setting->editSetting('retargeting', $settings);
    }
}