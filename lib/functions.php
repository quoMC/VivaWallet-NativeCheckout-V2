<?php

class FCM_NativeCheckout2{

    public static function Initialize($config){

        global $MERCHANT_ID;
        global $API_KEY;
        global $CLIENT_ID;
        global $CLIENT_SECRET;
        global $PAYMENT_SOURCE;
        global $REQUEST_URL;

                $MERCHANT_ID = $config['MERCHANT_ID'];
                $API_KEY = $config['API_KEY'];
                $CLIENT_ID = $config['CLIENT_ID'];
                $CLIENT_SECRET = $config['CLIENT_SECRET'];
                $PAYMENT_SOURCE = $config['PAYMENT_SOURCE'];
                $REQUEST_URL = 'https://www.vivapayments.com';
            }

    private static function Call_API($postUrl,$postobject){

        global $MERCHANT_ID;
        global $API_KEY;
        global $REQUEST_URL;

        $DATA = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $MERCHANT_ID . ':' . $API_KEY )
            ),
            'body' => $postobject
        );

        $response = wp_remote_post( $REQUEST_URL.$postUrl , $DATA );

        if(wp_remote_retrieve_response_code( $response ) !== 200)
        {
            return FALSE;
        }
        return json_decode(wp_remote_retrieve_body( $response ));

    }

    public static function Get_Access_Token(){

        global $CLIENT_ID;
        global $CLIENT_SECRET;

        $curl = curl_init();

        $DATA = array(
            'body' => array(
                'client_id' => $CLIENT_ID,
                "client_secret" => $CLIENT_SECRET,
                "grant_type" => "client_credentials"
            )

        );

                $response = wp_remote_post( 'https://accounts.vivapayments.com/connect/token' , $DATA );

        if (wp_remote_retrieve_response_code( $response ) !== 200) {
            throw new Exception('Viva Payments | Native Checkout Error: There was a connection or authentication failure when attempting to get an access_token. Please check your Client ID and Client Secret.');
        } else {
            return json_decode(wp_remote_retrieve_body( $response ))->access_token;
        }

    }

    public static function Create_Order($data){

        global $PAYMENT_SOURCE;

        $AMOUNT = $data['AMOUNT'];
        $ORDERNO = $data['ORDER_NO'];
        $ORDERFNAME = $data['ORDER_F_NAME'];
        $ORDERLNAME = $data['ORDER_L_NAME'];
        $ORDEREMAIL = $data['ORDER_EMAIL'];
        $ORDERTEL = $data['ORDER_TEL'];

        $obj=array(
            'Amount' => $AMOUNT,
            'SourceCode' => $PAYMENT_SOURCE,
            'CustomerTrns' => 'Big K Charcoal, GB',
            'MerchantTrns' => '#' . $ORDERNO,
            'Tags' => $_SERVER['HTTP_USER_AGENT'],
            'FullName' => $ORDERFNAME . ' ' . $ORDERLNAME,
            'Email' => $ORDEREMAIL,
            'Phone' => $ORDERTEL
        );

        $resultObj = FCM_NativeCheckout2::Call_API('/api/orders',$obj);

        if($resultObj !== FALSE) {
            if ($resultObj->ErrorCode==0) {
                return $resultObj->OrderCode;
            } else {
                throw new Exception($resultObj->ErrorText);
                return 0;
            }
        } else {
            throw new Exception('Viva Payments | Native Checkout Error: There was a connection or authentication failure when attempting to create an order. Please check your API key and Merchant ID.');
            return 0;
        }
    }

    public static function Transactions($data){

        global $PAYMENT_SOURCE;

        $ORDER_CODE = $data['ORDER_CODE'];
        $CHARGE_TOKEN = $data['CHARGE_TOKEN'];

        $obj=array(
            'OrderCode'=>$ORDER_CODE,
            'SourceCode' => $PAYMENT_SOURCE,
            'CreditCard'=> array(
                'token' => $CHARGE_TOKEN
                )
            );

        $resultObj = FCM_NativeCheckout2::Call_API('/api/transactions',$obj);

        if ($resultObj !== FALSE) {
            if ($resultObj->ErrorCode == 0 && $resultObj->StatusId == "F") {
                return array(
                    'WAS_SUCCESSFUL' => TRUE,
                    'TRANSACTION_ID' => $resultObj->TransactionId,
                    'ERROR_CODE' => $resultObj->ErrorCode,
                    'ERROR_TEXT' => $resultObj->ErrorText
                );
            } else {
                return array(
                    'WAS_SUCCESSFUL' => FALSE,
                    'ERROR_CODE' => $resultObj->ErrorCode,
                    'ERROR_TEXT' => $resultObj->ErrorText,
                    'STATUS_ID' => $resultObj->StatusId,
                    'EVENT_ID' => $resultObj->EventId
                );
            }
        } else {
                throw new Exception('Viva Payments | Native Checkout Error: There was a connection or authentication failure when attempting to charge the card. Please check your API key and Merchant ID.');
                return array(
                    'WAS_SUCCESSFUL' => FALSE,
                    'ERROR_CODE' => $resultObj->ErrorCode,
                    'ERROR_TEXT' => $resultObj->ErrorText,
                    'STATUS_ID' => $resultObj->StatusId,
                    'EVENT_ID' => $resultObj->EventId
                );
            }
    }

    public static function Complete_Woo_Order($data){

        $ORDER_ID = $data['ORDER_ID'];
        $TRANSACTION_ID = $data['TRANSACTION_ID'];

        global $woocommerce;
        $order = new WC_Order($ORDER_ID);
        $order->payment_complete();
        $order->add_order_note(__('Transaction ID: ', 'FCM_VivaPayments_NativeCheckout_2_Gateway') . $TRANSACTION_ID, 1);

    }

    public static function Fail_Woo_Order($data){

        $ORDER_ID = $data['ORDER_ID'];
        $STATUS_ID = $data['STATUS_ID'];
        $ERROR_CODE = $data['ERROR_CODE'];
        $ERROR_TEXT = $data['ERROR_TEXT'];
        $EVENT_ID = $data['EVENT_ID'];

        global $woocommerce;
        $order = new WC_Order($ORDER_ID);
        $order->update_status('failed', '');
        $order->add_order_note("Event: " . $EVENT_ID . ", Status Id: " . $STATUS_ID . ", Code: " . $ERROR_CODE . ", Message: " . $ERROR_TEXT);
    }

}


?>