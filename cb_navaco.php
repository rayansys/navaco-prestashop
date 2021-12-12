<?php
/**d
 * @package    navaco payment module
 * @author     Navaco
 * @copyright  2021  navaco.ir
 * @version    1.00
 */
@session_start();

if (isset($_GET['do']))
{
	include (dirname(__FILE__) . '/../../config/config.inc.php');
	include (dirname(__FILE__) . '/../../header.php');
	include_once (dirname(__FILE__) . '/navaco.php');
	$navaco = new navaco();

	if ($_GET['do'] == 'payment')
	{
		$navaco -> do_payment($cart);
	} else {
		if (isset($_GET['id']) && isset($_GET['amount']) && isset($_POST['Data']))
		{
            $data = $_POST["Data"];
            $data = json_decode($data);
			$orderId 	= $_GET['id'];
			$amount 	= $_GET['amount'];
	
			if (isset($_SESSION['order' . $orderId]))
			{
				$hash = Configuration::get('navaco_HASH');
				$hash = md5($orderId . $amount . $hash);

				if ($hash == $_SESSION['order' . $orderId])
				{
					$MerchantID = Configuration::get('navaco_API');
					$username = Configuration::get('navaco_USERNAME');
					$password = Configuration::get('navaco_PASSWORD');

                    $postField = [
                        "CARDACCEPTORCODE"=>$MerchantID,
                        "USERNAME"=>$username,
                        "USERPASSWORD"=>$password,
                        "PAYMENTID"=>$orderId,
                        "RRN"=>$data->RRN,
                    ];
                    $result = $navaco->callCurl($postField,"Confirm");


					if (isset($result->ActionCode) && (int)$result->ActionCode === 0)
					{						
						error_reporting(E_ALL);
						$au = $data->RRN;
						$navaco -> validateOrder($orderId, _PS_OS_PAYMENT_, $amount, $navaco -> displayName, "سفارش تایید شده / کد رهگیری {$au}", array(), $cookie -> id_currency);
						$_SESSION['order' . $orderId] = '';
						Tools::redirect('history.php');
					} else {						
						echo $navaco -> error($navaco -> l('There is a problem.') . ' (' . $result->ActionCode . ')<br/>' . $navaco -> l('Authority code') . ' : ' . $data->RRN);
					}
				} else {
					echo $navaco -> error($navaco -> l('There is a problem.'));
				}
			} else {
				echo $navaco -> error($navaco -> l('There is a problem.'));
			}
		} else {
			echo $navaco -> error($navaco -> l('There is a problem.'));
		}
	}
	include_once (dirname(__FILE__) . '/../../footer.php');
} else {
	_403();
}
function _403() {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}