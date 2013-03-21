<?php
/**
 * ֧�����������
 * @author qianlei 12-9-5
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

//language
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipaybank.php';
if (file_exists($payment_lang))
{
    global $_LANG;
    include_once($payment_lang);
}

/* ģ��Ļ�����Ϣ */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;
    /* ���� */
    $modules[$i]['code']    = basename(__FILE__, '.php');
    /* ������Ӧ�������� */
    $modules[$i]['desc']    = 'alipaybank_desc';
    /* �Ƿ�֧�ֻ������� */
    $modules[$i]['is_cod']  = '0';
    /* �Ƿ�֧������֧�� */
    $modules[$i]['is_online']  = '1';
    /* ���� */
    $modules[$i]['author']  = 'qianlei';
    /* ��ַ */
    $modules[$i]['website'] = 'http://www.alipay.com';
    /* �汾�� */
    $modules[$i]['version'] = '1.0.0';
    /* ������Ϣ */
    $modules[$i]['config']  = array(
        array('name' => 'alipaybank_account','type' => 'text',   'value' => ''),
        array('name' => 'alipaybank_key','type' => 'text',   'value' => ''),
        array('name' => 'alipaybank_partner','type' => 'text',   'value' => ''),
        array('name' => 'alipaybank_pay_method','type' => 'select', 'value' => '')
    );
    return;
}

class alipaybank
{
    public $alipaybank_config = array();

    //�������캯��
    public function __construct()
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = 'alipaybank' AND enabled = 1";
        if($payment = $GLOBALS['db']->getRow($sql))
        {
            $data = unserialize_config($payment['pay_config']);
            $this->alipaybank_config = array(
                'partner'=>$data['alipaybank_partner'],
                'key'=>$data['alipaybank_key'],
                'seller_email'=>$data['alipaybank_account'],
                'pay_method'=>$data['alipaybank_pay_method'],
                'return_url'=>$GLOBALS['ecs']->url().'respond_alipaybank.php',
                'notify_url'=>$GLOBALS['ecs']->url().'respond_alipaybank.php',
                'sign_type'=>'MD5',
                'input_charset'=>defined('EC_CHARSET') ? EC_CHARSET : 'utf-8',
                'transport'=>'http',
            );
            unset($data);
        }
    }

    /**
     * @param array $order  ������Ϣ
     * @param array $payment
     * @param boolean $url �Ƿ�ֻ����url
     * @return string
     */
    public function get_code($order, $payment,$url=false)
    {
        if (!$this->alipaybank_config)
            return '';

        switch ($this->alipaybank_config['pay_method'])
        {
            case '0':$service = 'trade_create_by_buyer';break;
            case '1':$service = 'create_partner_trade_by_buyer';break;
            case '2':$service = 'create_direct_pay_by_user';break;
        }

        $parameter = array(
            //��������
            'service'=>$service,
            'partner'=>$this->alipaybank_config['partner'],
            '_input_charset'=>$this->alipaybank_config['input_charset'],
            'notify_url'=> $this->alipaybank_config['notify_url'],
            'return_url'=> $this->alipaybank_config['return_url'],
            //ҵ�����
            'out_trade_no'=> $order['order_sn'] . $order['log_id'],
            'subject'=> $order['order_sn'],
            'payment_type'=>1,
            'price'=> $order['order_amount'],
            'quantity'=>1,
            'paymethod'=>'bankPay',
            //Ĭ���й���������
            'defaultbank'=>(isset($order['alipaybank_code']) && $order['alipaybank_code']) ? $order['alipaybank_code'] : 'ICBCB2C',
            //������Ϣ
            'seller_email'=>$this->alipaybank_config['seller_email'],
        );

        //alipay bank lib
        require_once (ROOT_PATH . 'includes/modules/payment/alipaybank/lib/alipay_service.class.php');

        $alipaybank_service = new AlipayService($this->alipaybank_config);
        $pay_url = $alipaybank_service->create_direct_pay_url_by_user($parameter);

        if ($url)
            return $pay_url;
        else
            return '<div style="text-align:center"><input type="button" onclick="window.open(\''.$pay_url.'\')" value="' .$GLOBALS['_LANG']['alipaybank_pay_button']. '" /></div>';
    }

    function respond()
    {
        //alipay bank lib
        require_once (ROOT_PATH . 'includes/modules/payment/alipaybank/lib/alipay_notify.class.php');
        $alipay_notify = new AlipayNotify($this->alipaybank_config);

        $is_post_request = isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST');
        if ($is_post_request)
        {
            $flag = $alipay_notify->verifyNotify();
        }
        else
        {
            $flag = $alipay_notify->verifyReturn();
        }

		$response = '';
		foreach ($_REQUEST as $k=>$v){$response .= $k.'='.$v.'&';}
		$order_log = array(
            'response'=>$response,
            'receive_response_at'=>time(),
            'alipaystatus'=>$flag ? 2 : 1,
        );
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_log'),$order_log,'UPDATE','order_sn="'.$_REQUEST['subject'].'"');

        if ($flag)
        {
            $out_trade_no	= $_REQUEST['out_trade_no'];	    //��ȡ������
            $trade_no		= $_REQUEST['trade_no'];	    	//��ȡ֧�������׺�

            $log_id = trim(str_replace($_REQUEST['subject'],'', $out_trade_no));

            //δ֪��֧��������,���͵Ĳ���out_trade_no��֧�����ش���out_trade_no��һ��
            //���޸������δ֪bug,��ȥ������Ĵ���
			if (!$log_id)
			{
                $sql = 'SELECT t1.log_id FROM '.
                        $GLOBALS['ecs']->table('pay_log').' AS t1,'.$GLOBALS['ecs']->table('order_info').' AS t2 '.
                        'WHERE t2.order_sn="'.$GLOBALS['db']->escape_string($_REQUEST['subject']).
                        '" AND t1.order_id=t2.order_id AND t1.order_type=0 AND t1.is_paid=0 AND t1.order_amount='.$_REQUEST['total_fee'];
                if ($log_id = $GLOBALS['db']->getOne($sql,true))
                {
                    $fuckbug = true;
                    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_log'),array('alipaystatus'=>3),'UPDATE','order_sn="'.$_REQUEST['subject'].'"');
                }
            }

            /* ���֧���Ľ���Ƿ���� */
            if (!isset($fuckbug))
            {
                if (!check_money($log_id, $_REQUEST['total_fee']))
                {
                    return false;
                }
            }

            if($_REQUEST['trade_status'] == 'TRADE_FINISHED')
            {
                //�жϸñʶ����Ƿ����̻���վ���Ѿ���������
                order_paid($log_id);
                return true;
            }
            else if ($_REQUEST['trade_status'] == 'TRADE_SUCCESS')
            {
                //�жϸñʶ����Ƿ����̻���վ���Ѿ���������
                order_paid($log_id, 2);
                return true;
            }
        }
        return false;
    }
}