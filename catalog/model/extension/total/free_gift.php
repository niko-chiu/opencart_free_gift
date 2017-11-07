<?php
class ModelExtensionTotalFreeGift extends Model {
	public function getTotal($total) {
		$this->load->language('total/free_gift');

    $sub_total = $this->cart->getSubTotal();
    $products  = $this->cart->getProducts();
    $gifts     = $this->getGifts();

    foreach($gifts as $giftk => $gift){
      $count_total = 0;
      foreach($products as $product){
        if(in_array($product['product_id'], explode(",", $gift['product_ids']))){
          $count_total += $product['total'];
        }
      }

      if($count_total >= $gift['amount_'.$this->session->data['currency']]){
        $total['totals'][] = array(
          'code'       => 'free_gift',
          'title'      => $gift['gift_'.$this->session->data['language']],
          'value'      => 0,
          'sort_order' => $this->config->get('total_free_gift_sort_order')
        );
      }
    }
  }
  
  public function getGifts(){
    $gifts = $this->db->query("SELECT * FROM ".DB_PREFIX."free_gift");

    foreach($gifts->rows as $gift){
      $data[$gift['free_gift_id']] = $gift;
    }

    return $data;
  }
}
