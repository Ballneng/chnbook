<?php

/**
 * ECSHOP ֧�������
 * ============================================================================
 * ��Ȩ���� 2005-2011 �Ϻ���������Ƽ����޹�˾������������Ȩ����
 * ��վ��ַ: http://www.ecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: liubo $
 * $Id: alipay.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipay.php';

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
    $modules[$i]['desc']    = 'alipay_desc';

    /* �Ƿ�֧�ֻ������� */
    $modules[$i]['is_cod']  = '0';

    /* �Ƿ�֧������֧�� */
    $modules[$i]['is_online']  = '1';

    /* ���� */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* ��ַ */
    $modules[$i]['website'] = 'http://www.alipay.com';

    /* �汾�� */
    $modules[$i]['version'] = '1.0.2';

    /* ������Ϣ */
    $modules[$i]['config']  = array(
        array('name' => 'alipay_account',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_key',               'type' => 'text',   'value' => ''),
        array('name' => 'alipay_partner',           'type' => 'text',   'value' => ''),
//        array('name' => 'alipay_real_method',       'type' => 'select', 'value' => '0'),
//        array('name' => 'alipay_virtual_method',    'type' => 'select', 'value' => '0'),
//        array('name' => 'is_instant',               'type' => 'select', 'value' => '0')
        array('name' => 'alipay_pay_method',        'type' => 'select', 'value' => '')
    );

    return;
}

/**
 * ��
 */
class alipay
{

    /**
     * ���캯��
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function alipay()
    {
    }

    function __construct()
    {
        $this->alipay();
    }

    /**
     * ����֧������
     * @param   array   $order      ������Ϣ
     * @param   array   $payment    ֧����ʽ��Ϣ
     * @param   boolean $url
     * @return string
     */
    function get_code($order, $payment,$url=false)
    {
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }
//        if (empty($payment['is_instant']))
//        {
//            /* δ��ͨ��ʱ���� */
//            $service = 'trade_create_by_buyer';
//        }
//        else
//        {
//            if (!empty($order['order_id']))
//            {
//                /* ��鶩���Ƿ�ȫ��Ϊ������Ʒ */
//                $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_goods').
//                        " WHERE is_real=1 AND order_id='$order[order_id]'";
//
//                if ($GLOBALS['db']->getOne($sql) > 0)
//                {
//                    /* �����д���ʵ����Ʒ */
//                    $service =  (!empty($payment['alipay_real_method']) && $payment['alipay_real_method'] == 1) ?
//                        'create_direct_pay_by_user' : 'trade_create_by_buyer';
//                }
//                else
//                {
//                    /* ������ȫ��Ϊ������Ʒ */
//                    $service = (!empty($payment['alipay_virtual_method']) && $payment['alipay_virtual_method'] == 1) ?
//                        'create_direct_pay_by_user' : 'create_digital_goods_trade_p';
//                }
//            }
//            else
//            {
//                /* �Ƕ�����ʽ������������Ʒ���� */
//                $service = (!empty($payment['alipay_virtual_method']) && $payment['alipay_virtual_method'] == 1) ?
//                    'create_direct_pay_by_user' : 'create_digital_goods_trade_p';
//            }
//        }

        $real_method = $payment['alipay_pay_method'];

        switch ($real_method){
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
        }

        $extend_param = 'isv^sh22';

        $parameter = array(
            'extend_param'      => $extend_param,
            'service'           => $service,
            'partner'           => $payment['alipay_partner'],
            //'partner'           => ALIPAY_ID,
            '_input_charset'    => $charset,
            'notify_url'        => return_url(basename(__FILE__, '.php')),
            'return_url'        => return_url(basename(__FILE__, '.php')),
            /* ҵ����� */
            'subject'           => $order['order_sn'],
            'out_trade_no'      => $order['order_sn'] . $order['log_id'],
            'price'             => $order['order_amount'],
            'quantity'          => 1,
            'payment_type'      => 1,
            /* �������� */
            'logistics_type'    => 'EXPRESS',
            'logistics_fee'     => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* ����˫����Ϣ */
            'seller_email'      => $payment['alipay_account'],
        );

        ksort($parameter);
        reset($parameter);

        $param = '';
        $sign  = '';

        foreach ($parameter AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $param = substr($param, 0, -1);
        $sign  = substr($sign, 0, -1). $payment['alipay_key'];
        //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;

        if ($url)
            return 'https://www.alipay.com/cooperate/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5';
        else
            return '<div style="text-align:center"><input type="button" onclick="window.open(\'https://www.alipay.com/cooperate/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5\')" value="' .$GLOBALS['_LANG']['pay_button']. '" /></div>';
    }

    /**
     * ��Ӧ����
     */
    function respond()
    {
        if (!empty($_POST))
        {
            foreach($_POST as $key => $data)
            {
                $_GET[$key] = $data;
            }
        }
        $payment  = get_payment($_GET['code']);
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = trim(str_replace($_GET['subject'], '', $_GET['out_trade_no']));

        /* �������ǩ���Ƿ���ȷ */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];

        $response = '';
        foreach ($_GET as $k=>$v){$response .= $k.'='.$v.'&';}
        $order_log = array(
            'response'=>$response,
            'receive_response_at'=>time(),
            'alipaystatus'=>md5($sign) == $_GET['sign'] ? 2 : 1,
        );
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_log'),$order_log,'UPDATE','order_sn="'.$_GET['subject'].'"');

        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        if (md5($sign) != $_GET['sign'])
        {
            return false;
        }

        //δ֪��֧��������,���͵Ĳ���out_trade_no��֧�����ش���out_trade_no��һ��
        //���޸������δ֪bug,��ȥ������Ĵ���
        if (!$order_sn)
        {
            $sql = 'SELECT t1.log_id FROM '.
                $GLOBALS['ecs']->table('pay_log').' AS t1,'.$GLOBALS['ecs']->table('order_info').' AS t2 '.
                'WHERE t2.order_sn="'.$GLOBALS['db']->escape_string($_GET['subject']).
                '" AND t1.order_id=t2.order_id AND t1.order_type=0 AND t1.is_paid=0 AND t1.order_amount='.$_GET['total_fee'];
            if ($order_sn = $GLOBALS['db']->getOne($sql,true))
            {
                $fuckbug = true;
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_log'),array('alipaystatus'=>3),'UPDATE','order_sn="'.$_GET['subject'].'"');
            }
        }

        /* ���֧���Ľ���Ƿ���� */
        if (!isset($fuckbug))
        {
            if (!check_money($order_sn, $_GET['total_fee']))
            {
                return false;
            }
        }

        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS')
        {
            /* �ı䶩��״̬ */
            order_paid($order_sn, 2);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_FINISHED')
        {
            /* �ı䶩��״̬ */
            order_paid($order_sn);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_SUCCESS')
        {
            /* �ı䶩��״̬ */
            order_paid($order_sn, 2);

            return true;
        }
        else
        {
            return false;
        }
    }
}

?>