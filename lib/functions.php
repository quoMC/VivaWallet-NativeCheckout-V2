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
                    'ERROR_TEXT' => $resultObj->ErrorText,
                    'STATUS_ID' => $resultObj->StatusId,
                    'EVENT_ID' => $resultObj->EventId
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
        $STATUS_ID = $data['STATUS_ID'];
        $ERROR_CODE = $data['ERROR_CODE'];
        $ERROR_TEXT = $data['ERROR_TEXT'];
        $EVENT_ID = $data['EVENT_ID'];

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

        switch ($STATUS_ID) {
            case "E" :
                $sSTATUS_ID = "The transaction was not completed because of an error";
            break;
        }

        switch ($EVENT_ID) {
            case "2061" :
                $sEVENT_ID = "Browser closed before authentication finished.";
            break;
            case "2062" :
                $sEVENT_ID = "3DS validation failed - Wrong password or two-factor auth code entered.";
            break;
            case "10001" :
                $sEVENT_ID = "Refer to card issuer - The issuing bank prevented the transaction.";
            break;
            case "10004" :
                $sEVENT_ID = "Pick up card - The card has been designated as lost or stolen.";
            break;
            case "10005" :
                $sEVENT_ID = "Do not honor - The issuing bank declined the transaction without an explanation.";
            break;
            case "10006" :
                $sEVENT_ID = "General error - The card issuer has declined the transaction as there is a problem with the card number.";
            break;
            case "10014" :
                $sEVENT_ID = "Invalid card number - The card issuer has declined the transaction as the credit card number is incorrectly entered or does not exist.";
            break;
            case "10200" :
                $sEVENT_ID = "The transaction was declined - incorrect card details and/or no available balance";
            break;
            case "10041" :
                $sEVENT_ID = "Lost card - The card issuer has declined the transaction as the card has been reported lost.";
            break;
            case "10043" :
                $sEVENT_ID = "Stolen card - The card has been designated as lost or stolen.";
            break;
            case "10051" :
                $sEVENT_ID = "Insufficient funds - The card has insufficient funds to cover the cost of the transaction.";
            break;
            case "10054" :
                $sEVENT_ID = "Expired card - The payment gateway declined the transaction because the expiration date is expired or does not match.";
            break;
            default:
                $sEVENT_ID = "The transaction was declined - incorrect card details and/or no available balance";
        }

        global $woocommerce;
        $order = new WC_Order($ORDER_ID);
        $order->update_status('failed', '');
        $order->add_order_note("[Error: " .  $EVENT_ID . "] " . $sSTATUS_ID . ". Reason: " . $sEVENT_ID);
    }

}


?>