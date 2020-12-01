<?php
class Mobbex_Mobbex_Helper_Data extends Mage_Core_Helper_Abstract
{
    const VERSION = '1.1.0';

	public function getHeaders() {
		$apiKey = Mage::getStoreConfig('payment/mobbex/api_key');
		$accessToken = Mage::getStoreConfig('payment/mobbex/access_token');

		return array(
            'cache-control: no-cache',
            'content-type: application/json',
            'x-api-key: ' . $apiKey,
            'x-access-token: ' . $accessToken,
        );
	}

	public function getModuleUrl($action, $queryParams) {
		return Mage::getUrl('mobbex/payment/' . $action, array('_secure' => true, '_query' => $queryParams)); 
	}

	public function getReference($order)
    {
        return 'mag_order_'.$order->getIncrementId().'_time_'.time();
	}

	private function getPlatform()
    {
        return [
            "name" => "magento_1",
            "version" => $this::VERSION
        ];
    }
	
    public function createCheckout($order)
    {
		// Init Curl
		$curl = curl_init();
		
        // Create an unique id
		$tracking_ref = $this->getReference($order);
		
		$items = array();
		$products = $order->getAllItems();
		
        foreach($products as $product) {
			$prd = Mage::helper('catalog/product')->getProduct($product->getId(), null, null);

            $items[] = array(
				"image" => (string)Mage::helper('catalog/image')->init($prd, 'image')->resize(150), 
				"description" => $product->getName(), 
				"quantity" => $product->getQtyOrdered(), 
				"total" => round($product->getPrice(),2) 
			);
		}

		// Add shipping item
		if (!empty($order->getShippingDescription())) {
            $items[] = [
                'description' => 'Envío: ' . $order->getShippingDescription(),
                'total' => $order->getShippingAmount(),
            ];
        }

		// Get Headers
		$headers = $this->getHeaders();

		// Return Query Params
		$queryParams = array('orderId' => $order->getIncrementId());

		$customer = [
			'name' => $order->getCustomerName(),
			'email' => $order->getCustomerEmail(),
			'phone' => !empty($order->getBillingAddress()) ? $order->getBillingAddress()->getTelephone() : null,
		];

		$return_url = $this->getModuleUrl('response', $queryParams);

		// Get domain from store URL
		$base_url = Mage::getBaseUrl();
		$domain = str_replace(['https://', 'http://'], '', $base_url);
		if (substr($domain, -1) === '/') {
			$domain = rtrim($domain, '/');
		}

        // Create data
        $data = array(
            'reference' => $tracking_ref,
            'currency' => 'ARS',
            'description' => 'Orden #' . $order->getIncrementId(),
			'test' => false, // TODO: Add to config
            'return_url' => $return_url,
            'items' => $items,
            'webhook' => $this->getModuleUrl('notification', $queryParams),
			'options' => [
				'button' => (Mage::getStoreConfig('payment/mobbex/embed') == true),
				'embed' => true,
				'domain' => $domain,
                'theme' => [
					'type' => 'light', 
					'colors' => null
				],
				'platform' => $this->getPlatform(),
			],
			'redirect' => 0,
			'total' => round($order->getGrandTotal(), 2),
			'customer' => $customer,
			'timeout' => 5,
		);

		curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.mobbex.com/p/checkout",
            CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => $headers
		]);
		
        $response = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
		
        if ($err) {
            d("cURL Error #:" . $err);
        } else {
			$res = json_decode($response, true);
			
			if($res['data']) {
				$res['data']['return_url'] = $return_url;
				return $res['data'];
			} else {
				// Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('mobbex/payment/cancel', array('_secure' => true)));

				// Restore Order
				if(Mage::getSingleton('checkout/session')->getLastRealOrderId()){
					if ($lastQuoteId = Mage::getSingleton('checkout/session')->getLastQuoteId()){
						$quote = Mage::getModel('sales/quote')->load($lastQuoteId);
						$quote->setIsActive(true)->save();
					}

					// Send error message
					Mage::getSingleton('core/session')->addError(Mage::helper('mobbex')->__('The payment has failed.'));

					 //Redirect to cart
					$this->_redirect('checkout/cart');
				}
			}
        }
    }
}
