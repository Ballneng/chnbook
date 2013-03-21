<?php

/**
* ECSHOP �Ϻ��������߲��
* ============================================================================
* ��Ȩ���� 2005-2008 �Ϻ���������Ƽ����޹�˾������������Ȩ����
* ��վ��ַ: http://www.ecshop.com��
* ----------------------------------------------------------------------------
* �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
* ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
* ============================================================================
* $Author:linys $
* $Id: chinapay.php 15013 2009-07-28 09:31:42Z linys $
*/

if (!defined('IN_ECS'))
{
die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/ChinaPay.php';

include_once(ROOT_PATH ."includes/modules/payment/chinapay/netpayclient_config.php");
include_once(ROOT_PATH ."includes/modules/payment/chinapay/netpayclient.php");

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
$modules[$i]['code'] = basename(__FILE__, '.php');

/* ������Ӧ�������� */
$modules[$i]['desc'] = 'chinapay_desc';

/* �Ƿ�֧�ֻ������� */
$modules[$i]['is_cod'] = '0';

/* �Ƿ�֧������֧�� */
$modules[$i]['is_online'] = '1';

/* ֧������ */
$modules[$i]['pay_fee'] = '1.5%';

/* ���� */
$modules[$i]['author'] = 'tolys';

/* ��ַ */
$modules[$i]['website'] = 'http://www.chinapay.com';

/* �汾�� */
$modules[$i]['version'] = '1.0.0';

/* ������Ϣ */
$modules[$i]['config'] = array(
array('name' => 'chinapay_account', 'type' => 'text', 'value' => ''),
array('name' => 'chinapay_merkey_file', 'type' => 'text', 'value' => ''),
array('name' => 'chinapay_pubkey_file', 'type' => 'text', 'value' => '')
);

return;
}

/**
* ��
*/
class chinapay
{
/**
* ���캯��
*
* @access public
* @param
*
* @return void
*/
function chinapay()
{
}

function __construct()
{
$this->chinapay();
}

/**
* ����֧������
* @param array $order ������Ϣ
* @param array $payment ֧����ʽ��Ϣ
*/
function get_code($order, $payment)
{
$MerId = trim($payment['chinapay_account']);
$OrdId = ecshopsn2chinapaysn($order['order_sn'], $MerId);
$TransAmt = formatamount($order['order_amount']);
$TransTime = date('His',time());
$CuryId = 'JPY'; 
$CountryId = "0081";
$TransDate = date('Ymd',time());
$TransType = '0001'; 
$Version = '20080515';
$GateId = '0001';
$TimeZone = "+07";
$DSTFlag = "1";
$ExtFlag = "00";
$data_vreturnurl = return_url(basename(__FILE__, '.php'));
$Priv1 = "test priv1"; 
$Priv2 = "test priv2"; 
$merkey_file= trim($payment['chinapay_merkey_file']);
//����˽Կ�ļ�, ����ֵ��Ϊ�����̻��ţ�����15λ
$merid = buildKey(ROOT_PATH . $merkey_file);
if(!$merid) {
echo "����˽Կ�ļ�ʧ�ܣ�";
exit;
}

//��������϶�����ϢΪ��ǩ����
$plain = $MerId . $OrdId . $TransAmt . $CuryId . $TransDate . $TransTime . $TransType. $CountryId . $TimeZone . $DSTFlag . $ExtFlag . $Priv1;
//����ǩ��ֵ������
$chkvalue = sign($plain);
if (!$chkvalue) {
echo "ǩ��ʧ�ܣ�";
exit;
}

$def_url = "<br /><form style='text-align:center;' method=post action='".REQ_URL_PAY."' target='_blank'>";
$def_url .= "<input type=HIDDEN name='MerId' value='".$MerId."'/>"; 
$def_url .= "<input type=HIDDEN name='OrdId' value='".$OrdId."'>";
$def_url .= "<input type=HIDDEN name='TransAmt' value='".$TransAmt."'>";
$def_url .= "<input type=HIDDEN name='CuryId' value='".$CuryId."'>"; 
$def_url .= "<input type=HIDDEN name='CountryId' value='".$CountryId."'>"; 
$def_url .= "<input type=HIDDEN name='TransDate' value='".$TransDate."'>";
$def_url .= "<input type=HIDDEN name='TransType' value='".$TransType."'>";
$def_url .= "<input type=HIDDEN name='Version' value='".$Version."'>";
$def_url .= "<input type=HIDDEN name='BgRetUrl' value='".$data_vreturnurl."'>";
$def_url .= "<input type=HIDDEN name='PageRetUrl' value='".$data_vreturnurl."'>";
$def_url .= "<input type=HIDDEN name='GateId' value='".$GateId."'>";
$def_url .= "<input type=hidden name='Priv1' value='".$Priv1."'>"; 
$def_url .= "<input type=hidden name='TimeZone' value='".$TimeZone."'>"; 
$def_url .= "<input type=hidden name='TransTime' value='".$TransTime."'>";
$def_url .= "<input type=hidden name='DSTFlag' value='".$DSTFlag."'>"; 
$def_url .= "<input type=hidden name='ExtFlag' value='".$ExtFlag."'>"; 
$def_url .= "<input type=hidden name='Priv2' value='".$Priv2."'>"; 
$def_url .= "<input type=HIDDEN name='ChkValue' value='".$chkvalue."'>";
$def_url .= "<input type=submit value='" .$GLOBALS['_LANG']['pay_button']. "'>";
$def_url .= "</form>";

return $def_url;
}

/**
* ��Ӧ����
*/
function respond()
{
//order_paid($v_oid);
//return true;
$payment = get_payment(basename(__FILE__, '.php'));

$merid = trim($_POST['merid']);
$orderno = trim($_POST['orderno']);
$transdate = trim($_POST['transdate']);
$amount = trim($_POST['amount']);
$currencycode = trim($_POST['currencycode']);
$transtype = trim($_POST['transtype']);
$status = trim($_POST['status']);
$checkvalue = trim($_POST['checkvalue']);
$v_gateid = trim($_POST['GateId']);
$v_Priv1 = trim($_POST['Priv1']);
/**
* ���¼�����Կ��ֵ
*/
$pubkey = $payment['chinapay_pubkey_file'];
$PGID = buildKey(ROOT_PATH . $pubkey);
if(!$PGID) {
echo "����˽Կ�ļ�ʧ�ܣ�";
exit;
}
$verify = verifyTransResponse($merid, $orderno, $amount, $currencycode, $transdate, $transtype, $status, $checkvalue);
if (!$verify) {
echo "��֤ǩ��ʧ�ܣ�";
exit;
}
/* �����Կ�Ƿ���ȷ */
if ($status == '1001')
{
$v_ordesn = chinapaysn2ecshopsn($orderno);
$order_id = get_order_id_by_sn($v_ordesn);
/* �ı䶩��״̬ */
order_paid($order_id);
return true;
}
else
{
return false;
}
}
}


/*
*���ض�����תΪ����������
*/
function ecshopsn2chinapaysn($order_sn, $vid){
if($order_sn && $vid){
$sub_vid = substr($vid, 10, 5);
$sub_start = substr($order_sn, 2, 4);
$sub_end = substr($order_sn, 6);
$temp = $pay_id;
return $sub_start . $sub_vid . $sub_end;
}
}

/*
*����������תΪ���ض�����
*/
function chinapaysn2ecshopsn($chinapaysn){
if($chinapaysn){ 
$year = date('Y',time());

return substr($year,0,2) . substr($chinapaysn, 0, 4) . substr($chinapaysn, 9) ;
}
}

/*
*��ʽ�����׽��Է�λ��λ��12λ���֡�
*/
function formatamount($amount){
if($amount){
if(!strstr($amount, ".")){
$amount = $amount.".00";
}
$amount = str_replace(".", "", $amount);
$temp = $amount;
for($i=0; $i< 12 - strlen($amount); $i++){
$temp = "0" . $temp;
}
return $temp;
}
}
?>