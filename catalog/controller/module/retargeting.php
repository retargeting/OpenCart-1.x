<?php
/*
 * CATALOG CONTROLLER FILE
 * catalog/controller/module
 * retargeting.php
 *
 * MODULE: Retargeting
 */

include_once 'Retargeting_REST_API_Client.php';

class ControllerModuleRetargeting extends Controller {

	public function index() {

		/* Load the language file */
		$this->language->load('module/retargeting');

        /* Load the required modules */
        $this->load->model('checkout/order');
        $this->load->model('tool/image');
        $this->load->model('setting/setting');
        $this->load->model('design/layout');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/product');

        /*
         * Get the base URL for our shop
         */
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['shop_url'] = $this->config->get('config_ssl');
        } else {
            $this->data['shop_url'] = $this->config->get('config_url');
        }
		
		/* Get the saved value from the admin area */
		$this->data['api_key_field'] = $this->config->get('api_key_field');
		$this->data['api_secret_field'] = $this->config->get('api_secret_field');

        $this->data['retargeting_setEmail'] = htmlspecialchars_decode($this->config->get('retargeting_setEmail'));
        $this->data['retargeting_addToCart'] = htmlspecialchars_decode($this->config->get('retargeting_addToCart'));
        $this->data['retargeting_clickImage'] = htmlspecialchars_decode($this->config->get('retargeting_clickImage'));
        $this->data['retargeting_commentOnProduct'] = htmlspecialchars_decode($this->config->get('retargeting_commentOnProduct'));
        $this->data['retargeting_mouseOverPrice'] = htmlspecialchars_decode($this->config->get('retargeting_mouseOverPrice'));
        $this->data['retargeting_setVariation'] = htmlspecialchars_decode($this->config->get('retargeting_setVariation'));

        /**
         * --------------------------------------
         *             Products feed
         * --------------------------------------
         **/
        /* XML Request intercepted, kill everything else and output */
        if (isset($_GET['xml']) && $_GET['xml'] === 'retargeting') {

            /* Modify the header */
            header('Content-Type: application/xml');

            /* Pull ALL products from the database */
            $products = $this->model_catalog_product->getProducts();

            $output = '<products>';
            foreach ($products as $product) {
                $product['quantity'] = (isset($product['quantity']) && !empty($product['quantity'])) ? 1 : 0;
                $product_url = htmlspecialchars($this->url->link('product/product', 'product_id=' . $product['product_id']), ENT_XML1);
                $product_image_url = $this->data['shop_url'] . 'image/' . $product['image'];
                $product_image_url = htmlspecialchars($product_image_url, ENT_XML1);
                $product_current_currency_price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), '', '', false);
                $product_current_currency_special = (isset($product['special']) ? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), '', '', false) : 0);

                $output .= "
                                <product>
                                    <id>{$product['product_id']}</id>
                                    <stock>{$product['quantity']}</stock>
                                    <price>{$product_current_currency_price}</price>
                                    <promo>{$product_current_currency_special}</promo>
                                    <url>{$product_url}</url>
                                    <image>{$product_image_url}</image>
                                </product>
                            ";
            }
            $output .= '</products>';
            echo $output;
            die();
        }
        /* --- END PRODUCTS FEED  --- */



        /**
         * ---------------------------------------------------------------------------------------------------------------------
         *
         * API poach && Discount codes generator
         *
         * ---------------------------------------------------------------------------------------------------------------------
         *
         *
         * ********
         * REQUEST:
         * ********
         * POST : key​=your_retargeting_key
         * GET : type​=0​&value​=30​&count​=3
         * * type => (Integer) 0​: Fixed; 1​: Percentage; 2​: Free Delivery;
         * * value => (Float) actual value of discount
         * * count => (Integer) number of discounts codes to be generated
         *
         *
         * *********
         * RESPONDS:
         * *********
         * json with the discount codes
         * * ['code1', 'code2', ... 'codeN']
         *
         *
         * STEP 1: check $_POST
         * STEP 2: add the discount codes to the local database
         * STEP 3: expose the codes to Retargeting
         * STEP 4: kill the script
         */
        if (isset($_POST['key']) && ($_POST['key'] === $this->data['api_key_field'])) {

            /* -------------------------------------------------------------
             * STEP 1: check $_POST and validate the API Key
             * -------------------------------------------------------------
             */

            /*
            include_once 'Retargeting_REST_API_Client.php';
            $client = new Retargeting_REST_API_Client($data['api_key_field'], $data['api_secret_field']);
            $client->setResponseFormat("json");
            $client->setDecoding(false);
            $client->setApiVersion('1.0');
            $client->setApiUri('https://retargeting.ro/api');
            */

            /* Check and adjust the incoming values */
            $discount_type = (isset($_GET['type'])) ? (filter_var($_GET['type'], FILTER_SANITIZE_NUMBER_INT)) : 'Received other than int';
            $discount_value = (isset($_GET['value'])) ? (filter_var($_GET['value'], FILTER_SANITIZE_NUMBER_FLOAT)) : 'Received other than float';
            $discount_codes = (isset($_GET['count'])) ? (filter_var($_GET['count'], FILTER_SANITIZE_NUMBER_INT)) : 'Received other than int';

            /* -------------------------------------------------------------
             * STEP 2: Generate and add to local database the discount codes
             * -------------------------------------------------------------
             */
            $generate_code = function() {
                return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 1) . substr(str_shuffle('AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz'), 0, 9);
            };

            $datetime = new DateTime();
            $start_date = $datetime->format('Y-m-d');
            $datetime->modify('+6 months');
            $expiration_date = $datetime->format('Y-m-d');

            for ($i = $discount_codes; $i > 0; $i--) {

                $code = $generate_code();
                $discount_codes_collection[] = $code;

                /* Discount type: Fixed value */
                if ($discount_type == 0) {

                    $this->db->query("
                                INSERT INTO `" . DB_PREFIX . "coupon` 
                                SET name = 'Discount Code: RTG_FX', 
                                    code = '{$code}', 
                                    discount = '{$discount_value}', 
                                    type = 'F', 
                                    total = '0', 
                                    logged = '0', 
                                    shipping = '0', 
                                    date_start = '{$start_date}', 
                                    date_end = '{$expiration_date}', 
                                    uses_total = '1', 
                                    uses_customer = '1', 
                                    status = '1', 
                                    date_added = NOW()
                                ");

                    /* Discount type: Percentage */
                } elseif ($discount_type == 1) {

                    $this->db->query("
                                INSERT INTO `" . DB_PREFIX . "coupon` 
                                SET name = 'Discount Code: RTG_PRCNT', 
                                    code = '{$code}', 
                                    discount = '{$discount_value}', 
                                    type = 'P', 
                                    total = '0', 
                                    logged = '0', 
                                    shipping = '0', 
                                    date_start = '{$start_date}', 
                                    date_end = '{$expiration_date}', 
                                    uses_total = '1', 
                                    uses_customer = '1', 
                                    status = '1', 
                                    date_added = NOW()
                                ");

                    /* Discount type: Free delivery */
                } elseif ($discount_type == 2) {

                    $this->db->query("
                                INSERT INTO `" . DB_PREFIX . "coupon` 
                                SET name = 'Discount Code: RTG_SHIP', 
                                    code = '{$code}', 
                                    discount = '0', 
                                    type = 'F', 
                                    total = '0', 
                                    logged = '0', 
                                    shipping = '1', 
                                    date_start = '{$start_date}', 
                                    date_end = '{$expiration_date}', 
                                    uses_total = '1', 
                                    uses_customer = '1', 
                                    status = '1', 
                                    date_added = NOW()
                                ");
                }

            } // End generating discount codes


            /* -------------------------------------------------------------
             * STEP 3: Return the newly generated codes
             * -------------------------------------------------------------
             */
            if (isset($discount_codes_collection) && !empty($discount_codes_collection)) {

                /* Modify the header */
                header('Content-Type: application/json');

                /* Output the json */
                echo json_encode($discount_codes_collection);

            }


            /* -------------------------------------------------------------
             * STEP 4: Kill the script
             * -------------------------------------------------------------
             */
            die();

        } // End $_GET processing
        /* --- END API URL & DISCOUNT CODES GENERATOR  --- */


        /* Set the template path for our module */
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/retargeting.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/module/retargeting.tpl';
        } else {
            $this->template = 'default/template/module/retargeting.tpl';
        }

        /**
        * --------------------------------------
        * Start gathering required data for View
        * --------------------------------------
        **/

        /*
         * Store the contents of the shopping cart
         *
         * returns Array ( [43::] => 1 [40::] => 3 )
         * [ProductID::SerializedOptions] => Quantity
         */
        $this->data['cart_products'] = isset($this->session->data['cart']) ? $this->session->data['cart'] : false;
        // We could use this too: $this->model_catalog_product->getProduct($product_id);

        /*
         * Get the current products from WishList
         *
         * return a numerical array containing the ID(s)
         * Array ( [0] => 40 [1] => 42 )
         */
        $this->data['wishlist'] = !empty($this->session->data['wishlist']) ? $this->session->data['wishlist'] : false;

        /*
         * Get the current Page
         *
         * returns 'layout/page' or ''
         * eg 'common/home', 'product/product', 'product/category'
         */
        $this->data['current_page'] = isset($this->request->get['route']) ? $this->request->get['route'] : false;

        /*
         * Get the current Category
         *
         * returns a numerical array containing the ID(s)
         * Array ( [0] => 20 ) for single category
         * Array ( [0] => 20 [1] => 27 ) for nested categories
         */
        $this->data['current_category'] = isset($this->request->get['path']) ? explode('_', $this->request->get['path']) : '';

        /*
         * Count the categories
         *
         * returns int, 1 for single category, > 1 for nested
         */
        $this->data['count_categories'] = (count($this->data['current_category']) > 1) ? (count($this->data['current_category'])) : 0;

        /*
         * Check if the user is logged in
         */
        if ($this->customer->isLogged()) {

            /* User is logged in */
            $this->data['user_logged_in'] = true;
            $this->data['customer_id'] = $this->customer->getId();
            $this->data['first_name'] = $this->customer->getFirstName();
            $this->data['last_name'] = $this->customer->getLastName();
            $this->data['email'] = $this->customer->getEmail();

        } else {

            /* User is a visitor */
            $this->data['user_logged_in'] = false;
        }


        /**
         * ------------------------------
         * Start Retargeting JS functions
         * ------------------------------
         **/

        // Gather all the js code and output a single variable
        $this->data['js_output'] = "/* --- START Retargeting --- */\n\n";


        /* DONE
         * 1. setEmail
         *
         */
        /* User is logged in, pull data from DB */
        if (isset($this->session->data['customer_id']) && !empty($this->session->data['customer_id'])) {
            $full_name = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
            $email_address = $this->customer->getEmail();
            $phone_number = $this->customer->getTelephone();

            $this->data['js_output'] .= "
                                        var _ra = _ra || {};
                                        _ra.setEmailInfo = {
                                            'email': '{$email_address}',
                                            'name': '{$full_name}',
                                            'phone': '{$phone_number}'
                                        };
                                        
                                        if (_ra.ready !== undefined) {
                                            _ra.setEmail(_ra.setEmailInfo)
                                        }
                                    ";
        } else {

            /* Listen on entire site for input data & validate it */
            $this->data['js_output'] .= "
                                        /* -- setEmail -- */
                                        function checkEmail(email) {
                                            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,9})+$/;
                                            return regex.test(email);
                                        };

                                        jQuery(document).ready(function($){
                                            $(\"{$this->data['retargeting_setEmail']}\").blur(function(){
                                                if ( checkEmail($(this).val()) ) {
                                                    _ra.setEmail({ 'email': $(this).val()});
                                                    console.log('setEmail fired!');
                                                }
                                            });
                                        });
                                        ";         
        }        


        /* DONE
         * 2. sendCategory
         *
         * if in category, send category + nested
         * categ id + categ name + parent categ + breadcrumb
         */
        if ($this->data['current_page'] === 'product/category') {

            $category_id_parent = $this->data['current_category'][0];
            $category_info_parent = $this->model_catalog_category->getCategory($category_id_parent);

            $this->data['sendCategory'] = '
                                            /* -- sendCategory -- */
                                            ';
            $this->data['sendCategory'] = 'var _ra = _ra || {}; ';
            $this->data['sendCategory'] .= '_ra.sendCategoryInfo = {';

            /* We have a nested category */
            if (count($this->data['current_category']) > 1) {

                for ($i = count($this->data['current_category']) - 1; $i > 0; $i--) {
                    $category_id = $this->data['current_category'][$i];
                    $category_info = $this->model_catalog_category->getCategory($category_id);
                    $this->data['sendCategory'] .= "
                                                    'id': {$category_id},
                                                    'name': '{$category_info['name']}',
                                                    'parent': {$category_id_parent},
                                                    'category_breadcrumb': [
                                                    ";
                    break;
                }

                array_pop($this->data['current_category']);

                for ($i = count($this->data['current_category']) - 1; $i >= 0; $i--) {
                    $category_id = $this->data['current_category'][$i];
                    $category_info = $this->model_catalog_category->getCategory($category_id);

                    if ($i === 0) {
                        $this->data['sendCategory'] .= "{
                                                        'id': {$category_id_parent},
                                                        'name': '{$category_info_parent['name']}',
                                                        'parent': false
                                                        }
                                                        ";
                        break;
                    }

                    $this->data['sendCategory'] .= "{
                                                    'id': {$category_id},
                                                    'name': '{$category_info['name']}',
                                                    'parent': {$category_id_parent}
                                                    },
                                                    ";
                }

                $this->data['sendCategory'] .= "]";

            /* We have a single category */
            } else {

                $this->data['category_id'] = $this->data['current_category'][0];
                $this->data['category_info'] = $this->model_catalog_category->getCategory($this->data['category_id']);
                $this->data['sendCategory'] .= "
                                                'id': {$this->data['category_id']},
                                                'name': '{$this->data['category_info']['name']}',
                                                'parent': false,
                                                'category_breadcrumb': []
                                                ";
            }

            //reset($this->data['current_category']);

            $this->data['sendCategory'] .= '};';
            $this->data['sendCategory'] .= "
                                            if (_ra.ready !== undefined) {
                                                _ra.sendCategory(_ra.sendCategoryInfo);
                                            };
                                            ";

            /* Send to output */
            $this->data['js_output'] .= $this->data['sendCategory'];
        }



        /* DONE
         * 3. sendBrand
         *
         * brand id + brand name
         */
        if ($this->data['current_page'] === 'product/manufacturer/info') {

            /* Check if the current product is part of a brand */
            if (isset($this->request->get['manufacturer_id']) && !empty($this->request->get['manufacturer_id'])) {
                $this->data['brand_id'] = $this->request->get['manufacturer_id'];
                $this->data['brand_name'] = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);
                $this->data['sendBrand'] = "var _ra = _ra || {};
                                            _ra.sendBrandInfo = {
                                                                'id': {$this->data['brand_id']},
                                                                'name': '{$this->data['brand_name']['name']}'
                                                                };
                                                                
                                                                if (_ra.ready !== undefined) {
                                                                    _ra.sendBrand(_ra.sendBrandInfo);
                                                                };
                                            ";

                /* Send to output */
                $this->data['js_output'] .= $this->data['sendBrand'];
            }
        }

        /*
         * 4. sendProduct
         * + likeFacebook
         * + setVariation
         */
        if ($this->data['current_page'] === 'product/product') {

            $product_id = $this->request->get['product_id'];
            $product_url = $this->url->link('product/product', 'product_id=' . $product_id);
            $product_details = $this->model_catalog_product->getProduct($product_id);
            $product_categories = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '{$product_id}'");
            $product_categories = $product_categories->rows; // Get all the subcategories for this product. Reorder its numerical indexes to ease the breadcrumb logic
            $product_current_currency_price = $this->currency->format($this->tax->calculate($product_details['price'], $product_details['tax_class_id'], $this->config->get('config_tax')), '', '', false);
            $product_current_currency_special = (isset($product_details['special']) ? $this->currency->format($this->tax->calculate($product_details['special'], $product_details['tax_class_id'], $this->config->get('config_tax')), '', '', false) : 0);
            
            /* Send the base info */
            $this->data['sendProduct'] = "
                                    var _ra = _ra || {}; _ra.sendProductInfo = {
                                    ";
            $this->data['sendProduct'] .= "
                                    'id': $product_id,
                                    'name': '{$product_details['name']}',
                                    'url': '{$product_url}',
                                    'img': '{$this->data['shop_url']}image/{$product_details['image']}',
                                    'price': {$product_current_currency_price},
                                    'promo': {$product_current_currency_special},
                                    'stock': ". (($product_details['quantity'] > 0) ? 1 : 0) .",
                                    ";

            /* Check if the product has a brand assigned */
            if (isset($product_details['manufacturer_id'])) {
                $this->data['sendProduct'] .= "
                                        'brand': {'id': {$product_details['manufacturer_id']}, 'name': '{$product_details['manufacturer']}'},
                                        ";
            } else {
                $this->data['sendProduct'] .= "
                                        'brand': false,
                                        ";
            }

            /* Check if the product has a category assigned */
            if (isset($product_categories) && !empty($product_categories)) {

                $product_cat = $this->model_catalog_product->getCategories($product_id);
                $product_cat_details = $this->model_catalog_category->getCategory($product_cat[0]['category_id']);

                // Resides in a parent category
                if (isset($product_cat_details['parent_id']) && ($product_cat_details['parent_id'] == 0)) {

                    $this->data['sendProduct'] .= "
                                            'category': {'id': {$product_cat_details['category_id']}, 'name': '{$product_cat_details['name']}', 'parent': false},
                                            'category_breadcrumb': []
                                            ";

                    // Resides in a nested category (child -> go up until parent)
                } else {

                    $product_cat_details_parent = $this->model_catalog_category->getCategory($product_cat_details['parent_id']);

                    // Get the top level category
                    $this->data['sendProduct'] .= "
                                            'category': {'id': {$product_cat_details['category_id']}, 'name': '{$product_cat_details['name']}', 'parent': {$product_cat_details['parent_id']}},
                                            'category_breadcrumb': [{'id': {$product_cat_details_parent['category_id']}, 'name': '{$product_cat_details_parent['name']}', 'parent': false}]
                                            ";

                } // Close elseif

            } // Close check if product has categories assigned

            $this->data['sendProduct'] .= "};"; // Close _ra.sendProductInfo
            $this->data['sendProduct'] .= "
                                            if (_ra.ready !== undefined) {
                                                _ra.sendProduct(_ra.sendProductInfo);
                                            };
                                            ";
            $this->data['js_output'] .= $this->data['sendProduct'];
            /* --- END sendProduct  --- */
            
            /*
             * likeFacebook
             */
            $this->data['likeFacebook'] = "
                                            if (typeof FB != 'undefined') {
                                                FB.Event.subscribe('edge.create', function () {
                                                    _ra.likeFacebook({$product_id});
                                                });
                                            };
                                            ";
            $this->data['js_output'] .= $this->data['likeFacebook'];
            /* --- END likeFacebook  --- */

            /*
             * setVariation
             */
            $this->data['setVariation'] = "
                                            var _ra = _ra || {};
                                            jQuery(document).ready(function($){
                                                $(\"{$this->data['retargeting_setVariation']}\").click(function(){
                                                    if ( $(this).val() != undefined ) {
                                                        _ra.setVariation({$product_id}, {
                                                            'code': '$(this).val()',
                                                            'details': {}
                                                        }, function() {
                                                            console.log('setVariation fired.');
                                                        });
                                                    }
                                                });
                                            });
            ";
            $this->data['js_output'] .= $this->data['setVariation'];
            /* --- END setVariation  --- */

        }  /* --- END --- */



        /* DONE -> implemented along with 11. mouseOverAddToCart
         * 5. addToCart
         *
         * product id, variation
         */
        $this->data['addToCart'] = '';


        /* CANNOT BE DONE
         * 6. setVariation
         *
         * product id, variation
         */
        $this->data['setVariation'] = '';


        /* DONE
         * 7. addToWishlist
         *
         * product id
         */
        if (($this->data['wishlist'])) {

            /* Prevent notices */
            $this->session->data['retargeting_wishlist_product_id'] = (isset($this->session->data['retargeting_wishlist_product_id']) && !empty($this->session->data['retargeting_wishlist_product_id'])) ? $this->session->data['retargeting_wishlist_product_id'] : '';

            /* While pushing out an item from the WishList with a lower array index, OpenCart won't reset the numerical indexes, thus generating a notice. This fixes it */
            $this->data['wishlist'] = array_values($this->data['wishlist']);

            if ($this->session->data['retargeting_wishlist_product_id'] != ($this->data['wishlist'][count($this->data['wishlist']) - 1])) {
                /* Get the total number of products in WishList; push the last added product into Retargeting */
                for ($i = count($this->data['wishlist']) - 1; $i >= 0; $i--) {
                    $product_id_in_wishlist = $this->data['wishlist'][$i] ;
                    break;
                }

                $this->data['addToWishlist'] = "
                                            var _ra = _ra || {};
                                            _ra.addToWishlistInfo = {
                                                                    'product_id': {$product_id_in_wishlist}
                                                                    };

                                            if (_ra.ready !== undefined) {
                                                _ra.addToWishlist(_ra.addToWishlistInfo.product_id);
                                            };
                                            ";

                /* We need to send the addToWishList event one time only. */
                $this->session->data['retargeting_wishlist_product_id'] = $product_id_in_wishlist;

                $this->data['js_output'] .= $this->data['addToWishlist'];
            }
        }


        /* DONE
         * 8. clickImage
         *
         * product id
         * div.image & img#image
         */
        if ($this->data['current_page'] === 'product/product') {
            $clickImage_product_info = $this->request->get['product_id'];
            $this->data['clickImage'] = "
                                            /* -- clickImage -- */
                                            jQuery(document).ready(function($) {
                                                if ($(\"{$this->data['retargeting_clickImage']}\").length > 0) {
                                                    $(\"{$this->data['retargeting_clickImage']}\").mouseover(function(){

                                                        _ra.clickImage({$clickImage_product_info}, function() {console.log('clickImage FIRED')});
                                                    });
                                                }
                                            });
                                        ";

            $this->data['js_output'] .= $this->data['clickImage'];
        }
        

        /* DONE
         * 9. commentOnProduct
         *
         * product id
         * a#button-review
         */
        if ($this->data['current_page'] === 'product/product') {
            $commentOnProduct_product_info = $this->request->get['product_id'];
            $this->data['commentOnProduct'] = "
                                                /* -- commentOnProduct -- */
                                                jQuery(document).ready(function($) {
                                                    if ($(\"{$this->data['retargeting_commentOnProduct']}\").length > 0) {
                                                        $(\"{$this->data['retargeting_commentOnProduct']}\").click(function() {
                                                            _ra.commentOnProduct({$commentOnProduct_product_info}, function() {console.log('commentOnProduct FIRED')});
                                                        });
                                                    }
                                                });
                                                ";

            $this->data['js_output'] .= $this->data['commentOnProduct'];
        }


        /* DONE
         * 10. mouseOverPrice
         *
         * product id, product price
         * div.price
         */
        if ($this->data['current_page'] === 'product/product') {
            $mouseOverPrice_product_id = $this->request->get['product_id'];
            $mouseOverPrice_product_info = $this->model_catalog_product->getProduct($mouseOverPrice_product_id);
            $mouseOverPrice_product_promo = (isset($mouseOverPrice_product_info['special'])) ? $mouseOverPrice_product_info['special'] : '0';
            $product_current_currency_price = $this->currency->format($this->tax->calculate($mouseOverPrice_product_info['price'], $mouseOverPrice_product_info['tax_class_id'], $this->config->get('config_tax')), '', '', false);
            $product_current_currency_special = (isset($mouseOverPrice_product_info['special']) ? $this->currency->format($this->tax->calculate($mouseOverPrice_product_info['special'], $mouseOverPrice_product_info['tax_class_id'], $this->config->get('config_tax')), '', '', false) : 0);
            


            $this->data['mouseOverPrice'] = "
                                            /* -- mouseOverPrice -- */
                                            jQuery(document).ready(function($) {
                                                if ($(\"{$this->data['retargeting_mouseOverPrice']}\").length > 0) {
                                                    $(\"{$this->data['retargeting_mouseOverPrice']}\").mouseover(function(){
                                                        if (typeof _ra.mouseOverAddToCart !== \"undefined\")
                                                            _ra.mouseOverPrice({$mouseOverPrice_product_id}, {
                                                                                                        'price': {$product_current_currency_price},
                                                                                                        'promo': {$product_current_currency_special}
                                                                                                        }, function() {console.log('mouseOverPrice FIRED')}
                                                            );
                                                    });
                                                }
                                            });
                                            ";

            $this->data['js_output'] .= $this->data['mouseOverPrice'];
        }


        /* DONE
         * 11. mouseOverAddToCart
         * 12. addToCart[v1]
         *
         * product id
         * input[type=text].button, #button-cart
         */
        if ($this->data['current_page'] === 'product/product') {
            $mouseOverAddToCart_product_id = $this->request->get['product_id'];
            $mouseOverAddToCart_product_info = $this->model_catalog_product->getProduct($mouseOverAddToCart_product_id);

            $this->data['mouseOverAddToCart'] = "
                                                /* -- mouseOverAddToCart & addToCart -- */
                                                jQuery(document).ready(function($){
                                                    if ($(\"{$this->data['retargeting_addToCart']}\").length > 0) {
                                                        /* -- mouseOverAddToCart -- */
                                                        $(\"{$this->data['retargeting_addToCart']}\").mouseover(function(){
                                                            if (typeof _ra.mouseOverAddToCart !== \"undefined\")
                                                                _ra.mouseOverAddToCart({$mouseOverAddToCart_product_id}, function(){console.log('mouseOverAddToCart FIRED')});
                                                        });

                                                        /* -- addToCart -- */
                                                        $(\"{$this->data['retargeting_addToCart']}\").click(function(){
                                                            _ra.addToCart({$mouseOverAddToCart_product_id}, false, function(){console.log('addToCart FIRED!')});
                                                        });
                                                    }
                                                });
                                                ";

            $this->data['js_output'] .= $this->data['mouseOverAddToCart'];
        }



        /*
         * saveOrder improvement
         */
        $this->session->data['RTG_ID'] = (isset($this->session->data['RTG_ID'])) ? $this->session->data['RTG_ID'] : 1;


        /* DONE
         * 13. saveOrder
         *
         * order no, order total, products ID, products qty, products price, variation code
         * checkout/success
         * input#button-confirm
         */
        if ($this->data['current_page'] === 'checkout/success') {

            $last_order_id = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` ORDER BY `order_id` DESC LIMIT 1");
            $this->data['order_id'] = $last_order_id->row['order_id'];
            $this->data['order_data'] = $this->model_checkout_order->getOrder($this->data['order_id']);

            $order_no = $this->data['order_data']['order_id'];
            $lastname = $this->data['order_data']['lastname'];
            $firstname = $this->data['order_data']['firstname'];
            $email = $this->data['order_data']['email'];
            $phone = $this->data['order_data']['telephone'];
            $state = $this->data['order_data']['shipping_country'];
            $city = $this->data['order_data']['shipping_city'];
            $address = $this->data['order_data']['shipping_address_1'];

            $discount_code = isset($this->session->data['retargeting_discount_code']) ? $this->session->data['retargeting_discount_code'] : 0;
            $total_discount_value = 0;
            $shipping_value = 0;
            $total_order_value = $this->data['order_data']['total'];;

            // Based on order id, grab the ordered products
            $order_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '{$this->data['order_id']}'");
            $this->data['order_product_query'] = $order_product_query;

            $this->data['saveOrder'] = "
                                        var _ra = _ra || {};
                                        _ra.saveOrderInfo = {
                                            'order_no': {$order_no},
                                            'lastname': '{$lastname}',
                                            'firstname': '{$firstname}',
                                            'email': '{$email}',
                                            'phone': '{$phone}',
                                            'state': '{$state}',
                                            'city': '{$city}',
                                            'address': '{$address}',
                                            'discount_code': '{$discount_code}',
                                            'discount': {$total_discount_value},
                                            'shipping': {$shipping_value},
                                            'total': {$total_order_value}
                                        };
                                        ";

            /* -------------------------------------- */
            $this->data['saveOrder'] .= "_ra.saveOrderProducts = [";
            for ($i = count($order_product_query->rows) - 1; $i >= 0; $i--) {
                if ($i == 0) {
                    $this->data['saveOrder'] .= "{
                                                'id': {$order_product_query->rows[$i]['product_id']},
                                                'quantity': {$order_product_query->rows[$i]['quantity']},
                                                'price': {$order_product_query->rows[$i]['price']},
                                                'variation_code': ''
                                                }";
                    break;
                }
                $this->data['saveOrder'] .= "{
                                            'id': {$order_product_query->rows[$i]['product_id']},
                                            'quantity': {$order_product_query->rows[$i]['quantity']},
                                            'price': {$order_product_query->rows[$i]['price']},
                                            'variation_code': ''
                                            },";
            }
            $this->data['saveOrder'] .= "];";
            /* -------------------------------------- */

            $this->data['saveOrder'] .= "
                                        if( _ra.ready !== undefined ) {
                                            _ra.saveOrder(_ra.saveOrderInfo, _ra.saveOrderProducts);
                                        }";
            /*
            * REST API Save Order
            */
            if($this->data['api_key_field'] && $this->data['api_key_field'] != '' &&  $this->data['api_secret_field'] && $this->data['api_secret_field'] != '') {

                $orderInfo = array(
                    'order_no' => $order_no,
                    'lastname' => $lastname,
                    'firstname'=> $firstname,
                    'email'=> $email,
                    'phone'=> $phone,
                    'state' => $state,
                    'city' => $city,
                    'address' => $address,
                    'discount_code' => $discount_code,
                    'discount' => $total_discount_value,
                    'shipping' => $shipping_value,
                    'total' => $total_order_value
                );

                $orderProducts = array();
                foreach($order_product_query->rows as $orderedProduct) {
                    $orderProducts[] = array(
                        'id' => $orderedProduct['product_id'],
                        'quantity'=> $orderedProduct['quantity'],
                        'price'=> $orderedProduct['price'],
                        'variation_code'=> ''
                    );
                }
                $orderClient = new Retargeting_REST_API_Client($this->data['api_key_field'], $this->data['api_secret_field']);
                $orderClient->setResponseFormat("json");
                $orderClient->setDecoding(false);
                $response = $orderClient->order->save($orderInfo,$orderProducts);
            }

            /*
             * Prevent
             * * sending saveOrder multiple times
             * * viewing saveOrder data in source
             */
            if (isset($this->session->data['RTG_ID']) && ($this->session->data['RTG_ID'] > 1)) {
                $this->session->data['RTG_ID'] = 0;
                $this->data['js_output'] .= $this->data['saveOrder'];
            }


        }


        /* DONE
         * 14. visitHelpPage
         *
         * true/false
         */
        if ($this->data['current_page'] === 'information/information') {
            $this->data['visitHelpPage'] = "
                                            /* -- visitHelpPage -- */
                                            var _ra = _ra || {};
                                            _ra.visitHelpPageInfo = {'visit' : true};
                                            if (_ra.ready !== undefined) {
                                                _ra.visitHelpPage();
                                            };
                                            ";
            $this->data['js_output'] .= $this->data['visitHelpPage'];
        }


        /* DONE
         * 15. checkoutIds
         *
         * product id
         */
        $checkout_modules = array('checkout/checkout', 'checkout/simplecheckout', 'checkout/ajaxquickcheckout', 'checkout/ajaxcheckout', 'checkout/quickcheckout', 'checkout/onepagecheckout', 'supercheckout/supercheckout');
        if ((in_array($this->data['current_page'], $checkout_modules) && $this->data['cart_products']) || ($this->data['current_page'] === 'checkout/cart' && $this->data['cart_products'])) {
            $cart_products = $this->cart->getProducts(); // Use this instead of session
            $this->data['checkoutIds'] = "
                                        /* -- checkoutIds -- */
                                        var _ra = _ra || {};
                                        _ra.checkoutIdsInfo = [
                                        ";

            $i_products = count($cart_products);
            foreach ($cart_products as $item => $detail) {
                $i_products--;
                $this->data['checkoutIds'] .= ($i_products > 0) ? $detail['product_id'] . "," : $detail['product_id'];
            }

            $this->data['checkoutIds'] .= "
                                            ];
                                            ";
            $this->data['checkoutIds'] .= "
                                            if (_ra.ready !== undefined) {
                                                _ra.checkoutIds(_ra.checkoutIdsInfo);
                                            };
                                            ";

            $this->data['js_output'] .= $this->data['checkoutIds'];

            /* saveOrder improvement: allow data exposure */
            $this->session->data['RTG_ID']++;
        }


        /* With the gathered data, output in .tpl */
        $this->data['js_output'] .= "\n/* --- END Retargeting Functions --- */\n";

		/*
		 * Render the output
		 */
		$this->render();
	}
}