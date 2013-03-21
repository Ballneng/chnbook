<?php

/**
 * ECSHOP ��������
 * ============================================================================
 * ��Ȩ���� 2005-2011 �Ϻ���������Ƽ����޹�˾������������Ȩ����
 * ��վ��ַ: http://www.ecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: liubo $
 * $Id: search.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);
require_once('360_safe3.php');
require(dirname(__FILE__) . '/includes/init.php');
//order language
require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/order.php');

require_once(ROOT_PATH . 'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_transaction.php');

$order_data = array();
$act = isset($_POST['act']) ? trim($_POST['act']) : '';

if($act == 'order_search')
{
    $where = '';
    //���ݶ����Ż����ֻ�������
    if (isset($_POST['search_type']))
    {
        $search_type = $_POST['search_type']=='order_sn' ? 'order_sn' : 'mobile';
        $search_value = trim($_POST['w']);
    }
    else
    {
        //������ҳ�Ķ�������
        $search_type =  $_POST['order_sn']  ? 'order_sn' : 'mobile';
        $search_value = $search_type=='order_sn' ? $_POST['order_sn'] : $_POST['mobile'];
    }

    if ($search_type == 'order_sn' && $search_value)
    {
        $where .= " AND order_sn='".$search_value."'";
    }
    else if ($search_type == 'mobile' && $search_value)
    {
        $where .= " AND mobile='".$search_value."'";
    }


	if($where)
	{
	    include_once('includes/lib_clips.php');
	    include_once('includes/lib_payment.php');
    
	    $sql = "SELECT  o.*,(" . order_amount_field('o.') . ") AS total_fee".
	           " FROM " . $ecs->table('order_info') . " AS o " .
	           " WHERE 1 ".$where." ORDER BY add_time DESC";

	    $data = $db->getAll($sql);

	    if (!empty($data))
        {
            foreach ($data as $row)
            {
                $order_data[$row['order_id']]['order_sn'] = $row['order_sn'];
                $order_data[$row['order_id']]['consignee'] = $row['consignee'];

                //goods name
                $sql = "SELECT goods_name FROM " . $ecs->table('order_goods').
                    " WHERE order_id=".$row['order_id'];

                if ($_tmp_goods = $db->getAll($sql))
                {
                    foreach ($_tmp_goods  as $goods){
                        $order_data[$row['order_id']]['goods'][] = $goods['goods_name'];
                    }
                }

                //order status
                $status = array(
                    $_LANG['os'][$row['order_status']],
                    $_LANG['ps'][$row['pay_status']],
                    $_LANG['ss'][$row['shipping_status']],
                );
                $order_data[$row['order_id']]['status'] = implode(',',$status);

                //shipping
                $order_data[$row['order_id']]['shipping_name'] = $row['shipping_name'];
                //����ʱ��
                $order_data[$row['order_id']]['shipping_time'] = $row['shipping_time'] ? local_date('Y-m-d H:i:s',$row['shipping_time']) : '';
                //��������
                $order_data[$row['order_id']]['shipping_invoice'] = $row['invoice_no'];

                //operation δ����
                if ($row['pay_status']==0 && $payment = payment_info($row['pay_id']))
                {
                    include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');
                    $pay_obj    = new $payment['pay_code'];

                    if ($payment['pay_code'] == 'alipaybank')
                    {
                        //֧��������
                        $sql = "SELECT `bankcode` FROM ".$ecs->table('order_alipaybank') .
                               "WHERE order_id=".$row['order_id'];
                        if ($bankcode = $db->getOne($sql))
                        {
                            $row['alipaybank_code'] = $bankcode;
                            $order_data[$row['order_id']]['operation'] = $pay_obj->get_code($row, unserialize_config($payment['pay_config']));
                        }
                    }
                    else
                    {
                        $order_data[$row['order_id']]['operation'] = $pay_obj->get_code($row, unserialize_config($payment['pay_config']));
                    }
                }
            }
        }
	}
}

$ur_here = '������ѯ';
$smarty->assign('act', $act);
$smarty->assign('search_type',$search_type ? $search_type : 'order_sn');
$smarty->assign('search_value',$search_value ? $search_value : '');
$smarty->assign('order_data',$order_data);
$smarty->assign('lang', $_LANG);   // ����״̬
$smarty->assign('url_format', $url_format);
assign_template();
assign_dynamic('search');
$position = assign_ur_here(0, $ur_here . ($_REQUEST['keywords'] ? '_' . $_REQUEST['keywords'] : ''));
$smarty->assign('page_title',$position['title']);    // ҳ�����
$smarty->assign('ur_here',$position['ur_here']);  // ��ǰλ��
//��ʾ�ط�ʡ���̲� qianlei 2012-08-22
$tmp_categorys = get_province_goods();
$smarty->assign('split_categories',array_chunk($tmp_categorys,4,true)); //�ָ�ķ�����
$smarty->assign('split_categories_show',true);
$smarty->assign('page_id','order_search');
$smarty->assign('helps',get_shop_help());
$smarty->display('order_search.dwt');