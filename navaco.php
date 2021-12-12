<?php
/**d
 * @package    rasacards payment module
 * @author     Milad Maldar
 * @copyright  2020  miladworkshop.ir
 * @version    1.00
 */
if (!defined('_PS_VERSION_'))
	exit ;

class navaco extends PaymentModule
{
	private $_html 			= '';
	private $_postErrors 	= array();
    private $url = "https://fcp.shaparak.ir/nvcservice/Api/v2/";
	public function __construct()
	{

		$this->name 			= 'navaco';
		$this->tab 				= 'payments_gateways';
		$this->version 			= '1.0';
		$this->author 			= 'Navaco';
		$this->currencies 		= true;
		$this->currencies_mode 	= 'radio';

		parent::__construct();

		$this->displayName 		= $this->l('navaco Payment Modlue');
		$this->description 		= $this->l('Online Payment With navaco');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');
		$config = Configuration::getMultiple(array('navaco_API','navaco_USERNAME','navaco_PASSWORD',));
		if (!isset($config['navaco_API']))
			$this->warning = $this->l('You have to enter your navaco key to use navaco for your online payments.');
		if (!isset($config['navaco_USERNAME']))
			$this->warning = $this->l('You have to enter your navaco username to use navaco for your online payments.');
		if (!isset($config['navaco_PASSWORD']))
			$this->warning = $this->l('You have to enter your navaco password to use navaco for your online payments.');
	}

	public function install()
	{
		if (!parent::install() || !Configuration::updateValue('navaco_API', '') || !Configuration::updateValue('navaco_USERNAME', '') || !Configuration::updateValue('navaco_PASSWORD', '') || !Configuration::updateValue('navaco_LOGO', '') || !Configuration::updateValue('navaco_HASH_KEY', $this->hash_key()) || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
			return false;
		else
			return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('navaco_API') || !Configuration::deleteByName('navaco_USERNAME') || !Configuration::deleteByName('navaco_PASSWORD') || !Configuration::deleteByName('navaco_LOGO') || !Configuration::deleteByName('navaco_HASH_KEY') || !parent::uninstall())
			return false;
		else
			return true;
	}

	public function hash_key()
	{
		$en 	= array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
		$one 	= rand(1, 26);
		$two 	= rand(1, 26);
		$three 	= rand(1, 26);

		return $hash = $en[$one] . rand(0, 9) . rand(0, 9) . $en[$two] . $en[$tree] . rand(0, 9) . rand(10, 99);
	}

	public function getContent()
	{

		if (Tools::isSubmit('navaco_setting'))
		{
			Configuration::updateValue('navaco_API', $_POST['pn_API']);
			Configuration::updateValue('navaco_USERNAME', $_POST['pn_USERNAME']);
			Configuration::updateValue('navaco_PASSWORD', $_POST['pn_PASSWORD']);
			Configuration::updateValue('navaco_LOGO', $_POST['pn_LOGO']);
			$this->_html .= '<div class="conf confirm">' . $this->l('تنظیمات با موفقیت ثبت و ذخیره شد') . '</div>';
		}

		$this->_generateForm();

		return $this->_html;
	}

	private function _generateForm()
	{
		$this->_html .= '<div align="center"><form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$this->_html .= $this->l('کد درگاه') . '<br/><br/>';
		$this->_html .= '<input type="text" name="pn_API" value="' . Configuration::get('navaco_API') . '" ><br/><br/>';
		$this->_html .= $this->l('نام کاربری درگاه') . '<br/><br/>';
		$this->_html .= '<input type="text" name="pn_USERNAME" value="' . Configuration::get('navaco_USERNAME') . '" ><br/><br/>';
		$this->_html .= $this->l('پسورد درگاه') . '<br/><br/>';
		$this->_html .= '<input type="text" name="pn_PASSWORD" value="' . Configuration::get('navaco_PASSWORD') . '" ><br/><br/>';
		$this->_html .= '<input type="submit" name="navaco_setting"';
		$this->_html .= 'value="' . $this->l('ثبت و ذخیره سازی تنظیمات') . '" class="button" />';
		$this->_html .= '</form><br/></div>';
	}

	public function do_payment($cart)
	{
		$MerchantID 	= Configuration::get('navaco_API');
		$username 	    = Configuration::get('navaco_USERNAME');
		$password 	    = Configuration::get('navaco_PASSWORD');
		$amount 		= (int)floatval(number_format($cart ->getOrderTotal(true, 3), 2, '.', ''));
		$orderId 		= $cart ->id;
		$callbackUrl 	= (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/navaco/cb_navaco.php?do=call_back&id=' . $cart ->id . '&amount=' . $amount;


        $postField = [
            "CARDACCEPTORCODE"=>$MerchantID,
            "USERNAME"=>$username,
            "USERPASSWORD"=>$password,
            "PAYMENTID"=>$orderId,
            "AMOUNT"=>$amount,
            "CALLBACKURL"=>$callbackUrl,
        ];
		$result = $this->callCurl($postField,"PayRequest");

		$hash 							= Configuration::get('navaco_HASH');
		$_SESSION['order' . $orderId] 	= md5($orderId . $amount . $hash);

		if (isset($result->ActionCode) && (int)$result->ActionCode === 0)
		{
			echo $this->success($this->l('Redirecting...'));
			echo '<script>window.location=("'. $result->RedirectUrl .'");</script>';
		} else {
			$result_status = (isset($result->ActionCode) && $result->ActionCode != "") ? $result->ActionCode : "Error connecting to web service";

			echo $this->error($this->l('There is a problem.') . ' (' . $result_status . ')');
		}
	}

	public function error($str)
	{
		return '<div class="alert error">' . $str . '</div>';
	}

	public function success($str)
	{
		echo '<div class="conf confirm">' . $str . '</div>';
	}

	public function hookPayment($params)
	{
		global $smarty;

		$smarty ->assign('navaco_logo', Configuration::get('navaco_LOGO'));

		if ($this->active)
			return $this->display(__FILE__, 'navacopayment.tpl');
	}

	public function hookPaymentReturn($params)
	{
		if ($this->active)
			return $this->display(__FILE__, 'zpconfirmation.tpl');
	}
    public function callCurl($postField,$action){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url.$action);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postField));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        $curl_exec = curl_exec($curl);
        curl_close($curl);
        return json_decode($curl_exec);
    }
}
// End of: rasacards.php
?>