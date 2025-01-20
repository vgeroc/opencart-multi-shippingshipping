<?php
class ControllerExtensionShippingMultiShipping extends Controller {
	private $error = array();

	public function index(){
		$this->load->language('extension/shipping/multi_shipping');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('localisation/geo_zone');
		$this->load->model('setting/store');
		$this->load->model('localisation/tax_class');
		$this->load->model('localisation/language');
		$this->load->model('customer/customer_group');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_multi_shipping', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/shipping/multi_shipping', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/multi_shipping', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/multi_shipping', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);


		if (isset($this->request->post['shipping_multi_shipping_status'])) {
			$data['shipping_multi_shipping_status'] = $this->request->post['shipping_multi_shipping_status'];
		} else if($this->config->has('shipping_multi_shipping_status')){
			$data['shipping_multi_shipping_status'] = (int)$this->config->get('shipping_multi_shipping_status');
		} else {
			$data['shipping_multi_shipping_status'] = 0;
		}

		if (isset($this->request->post['shipping_multi_shipping_sort_order'])) {
			$data['shipping_multi_shipping_sort_order'] = $this->request->post['shipping_multi_shipping_sort_order'];
		} else if($this->config->has('shipping_multi_shipping_sort_order')){
			$data['shipping_multi_shipping_sort_order'] = (int)$this->config->get('shipping_multi_shipping_sort_order');
		} else {
			$data['shipping_multi_shipping_sort_order'] = 0;
		}

		if (isset($this->request->post['shipping_multi_shipping_method'])) {
			$data['shipping_multi_shipping_method'] = $this->request->post['shipping_multi_shipping_method'];
		} else if($this->config->has('shipping_multi_shipping_method')){
			$data['shipping_multi_shipping_method'] = $this->config->get('shipping_multi_shipping_method');
		} else {
			$data['shipping_multi_shipping_method'] = array();
		}
				
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['method'])) {
			$data['error_method'] = $this->error['method'];
		} else {
			$data['error_method'] = array();
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/multi_shipping', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/multi_shipping')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if(isset($this->request->post['shipping_multi_shipping_method'])){
			$methods = $this->request->post['shipping_multi_shipping_method'];
			
			foreach($methods as $key => $method){
				foreach($method['name'] as $lkey => $langauge){
					if(empty($langauge) || trim($langauge) == ""){
						$this->error['method'][$key]['name'][$lkey] = $this->language->get('error_name');
					}
				}
				
				if($method['rate_type'] == 'flat' && empty($method['flat_rate'])) {
					$this->error['method'][$key]['flat_rate'][$key] = $this->language->get('error_rate');
				}
				
				if($method['rate_type'] == 'price' && empty($method['price_rate'])) {
					$this->error['method'][$key]['price_rate'][$key] = $this->language->get('error_rate');
				}
				
				if($method['rate_type'] == 'weight' && empty($method['weight_rate'])) {
					$this->error['method'][$key]['weight_rate'][$key] = $this->language->get('error_rate');
				}
				
				if (!isset($method['store_id'])) {
					$this->error['method'][$key]['store_id'][$key] = $this->language->get('error_store');
				}
				
				if (!isset($method['customer_group_id'])) {
					$this->error['method'][$key]['customer_group_id'][$key] = $this->language->get('error_group');
				}
			}
		}
		
		if ($this->error) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		
		return !$this->error;
	}
}