<?php
class ControllerExtensionTotalFreeGift extends Controller {
  public function index(){
    $this->load->language('extension/total/free_gift');
    $this->load->model('setting/setting');
    $this->load->model('localisation/language');

    $this->document->setTitle($this->language->get('heading_title'));

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      $this->model_setting_setting->editSetting('total_free_gift', [
        'total_free_gift_status'     => $this->request->post['free_gift_status'],
        'total_free_gift_sort_order' => $this->request->post['free_gift_sort_order']
      ]);

      $this->editGift();

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
    }

    $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

    $data['gifts']                = $this->getGifts();
    $data['languages']            = $this->model_localisation_language->getLanguages();
    $data['free_gift_status']     = $this->config->get('total_free_gift_status');
    $data['free_gift_sort_order'] = $this->config->get('total_free_gift_sort_order');

    $data['action_save']      = $this->url->link('extension/total/free_gift', 'user_token=' . $this->session->data['user_token'], true);
    $data['action_cancel']    = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

    $data['time']       = time();
    $data['user_token'] = $this->session->data['user_token'];

    $data['breadcrumbs'] = [
      [
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
      ],[
        'text' => $this->language->get('text_total'),
        'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
      ],[
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/total/free_gift', 'user_token=' . $this->session->data['user_token'], true)
      ]
    ];

    $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/total/free_gift', $data));
  }

  public function editGift(){
    $this->uninstall();
    $this->install();

    $gifts = isset($this->request->post['gifts']) ? $this->request->post['gifts'] : [];

    foreach($gifts as $gift){
      // if(!empty($gift['product_ids']) && preg_match('/^[0-9][0-9,]*(?<!,)$/', $gift['product_ids']) && !empty( $gift['amount'])){
      if(!empty($gift['product_ids']) && !empty( $gift['amount'])){
        $sql = "INSERT INTO ".DB_PREFIX."free_gift (`product_ids`, `amount`, ";
        
        foreach($this->model_localisation_language->getLanguages() as $language){
          $sql .= "`gift_".$language['code']."`,";
        }
  
        $sql = substr($sql, 0, -1);
        $sql .= ") VALUES ('".implode(',', $gift['product_ids'])."', ".$gift['amount'].", ";
      }else{
        break;
      }

      foreach($this->model_localisation_language->getLanguages() as $language){
        if(!empty($gift['gift'][$language['code']])){
          $sql .= "'".addslashes($gift['gift'][$language['code']])."',";
        }
      }

      $sql = substr($sql, 0, -1);
      $sql .= ")";

      $this->db->query($sql);
    }
  }

  public function getGifts(){
    $this->load->model('catalog/product');
    $result = $this->db->query('SELECT * FROM '.DB_PREFIX.'free_gift')->rows;

    for($i=0; $i<count($result); $i++){
      $product_ids = explode(',', $result[$i]['product_ids']);

      $products = [];
      foreach($product_ids as $id){
        $product = $this->model_catalog_product->getProduct($id);
        $products[] = [
          'id'   => $id,
          'name' => $product['name']
        ];
      }

      $result[$i]['products'] = $products;
    }

    return $result;
  }

  public function install(){
    $this->load->model('localisation/language');

    $sql = "CREATE TABLE IF NOT EXISTS ".DB_PREFIX."free_gift (
      `free_gift_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `product_ids` text NOT NULL,
      `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',";
      

    foreach($this->model_localisation_language->getLanguages() as $language){
      $sql .= "`gift_".$language['code']."` text NOT NULL,";
    }

    $sql = substr($sql, 0, -1);
    $sql .= ")";

    $this->db->query($sql);
  }

  public function uninstall(){
    $this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."free_gift");
  }
}