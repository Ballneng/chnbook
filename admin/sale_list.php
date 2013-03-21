<?php

/**
 * ECSHOP ������ϸ�б����
 * ============================================================================
 * ��Ȩ���� 2005-2011 �Ϻ���������Ƽ����޹�˾������������Ȩ����
 * ��վ��ַ: http://www.ecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ�������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã�������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: liubo $
 * $Id: sale_list.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/statistic.php');
$smarty->assign('lang', $_LANG);

if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    /* ���Ȩ�� */
    check_authz_json('sale_order_stats');
    if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
    }
    /*------------------------------------------------------ */
    //--Excel�ļ�����
    /*------------------------------------------------------ */
    if ($_REQUEST['act'] == 'download')
    {
        $file_name = $_REQUEST['start_date'].'_'.$_REQUEST['end_date'] . '_sale';
        $goods_sales_list = get_sale_list(false);
        header("Content-type: application/vnd.ms-excel; charset=GB2312");
        header("Content-Disposition: attachment; filename=$file_name.xls");

        /* �ļ����� */
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date']. $_LANG['to'] .$_REQUEST['end_date']. $_LANG['sales_list']) . "\t\n";

        /* ��Ʒ����,������,��Ʒ����,���ۼ۸�,�������� */
        //NJPHP
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['goods_name']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['order_sn']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['amount']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['sell_price']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�����۸�') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '֧����ʽ') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '���ͷ�ʽ') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '��ݵ���') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '����״̬') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['sell_date']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '����ʱ��') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '����ʱ��') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�ͻ�����') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�ͻ��绰') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�ͻ��ֻ�') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�ͻ�����') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '�ͻ���ע') . "\t\n";

        foreach ($goods_sales_list['sale_list_data'] AS $key => $value)
        {
        	//NJPHP
        	if($value['money_paid'] != '��0.00Ԫ'){
        		$value['print_value'] = $value['money_paid'];
        	}else if($value['order_amount'] != '��0.00Ԫ'){
        		$value['print_value'] = $value['order_amount'];
        	}else if($value['surplus'] != '��0.00Ԫ'){
        		$value['print_value'] = $value['surplus'];
        	}
        	
        	//������״̬;0δȷ��,1ȷ��,2��ȡ��,3��Ч,4�˻�,5�ѷֵ�
        	if($value['order_status'] == '0'){
        		$value['order_status'] = 'δȷ��';
        	}else if($value['order_status'] == '1'){
        		$value['order_status'] = '��ȷ��';
        	}else if($value['order_status'] == '2'){
        		$value['order_status'] = '��ȡ��';
        	}else if($value['order_status'] == '3'){
        		$value['order_status'] = '��  Ч';
        	}else if($value['order_status'] == '4'){
        		$value['order_status'] = '��  ��';
        	}else if($value['order_status'] == '5'){
        		$value['order_status'] = '�ѷֵ�';
        	}
        	
        	//��Ʒ�������;0δ����,1�ѷ���,2���ջ�,4�˻�
        	if($value['shipping_status'] == '0'){
        		$value['shipping_status'] = 'δ����';
        	}else if($value['shipping_status'] == '1'){
        		$value['shipping_status'] = '�ѷ���';
        	}else if($value['shipping_status'] == '2'){
        		$value['shipping_status'] = '���ջ�';
        	}else if($value['shipping_status'] == '3'){
        		$value['shipping_status'] = '�����';
        	}else if($value['shipping_status'] == '4'){
        		$value['shipping_status'] = '��  ��';
        	}
        	
        	//֧��״̬;0δ����;1������;2�Ѹ���
        	if($value['pay_status'] == '0'){
        		$value['pay_status'] = 'δ����';
        	}else if($value['pay_status'] == '1'){
        		$value['pay_status'] = '������';
        	}else if($value['pay_status'] == '2'){
        		$value['pay_status'] = '�Ѹ���';
        	}
        	
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', '[ ' . $value['order_sn'] . ' ]') . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_num']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['sales_price']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['print_value']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['pay_name']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['shipping_name']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['invoice_no']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['order_status'].' '.$value['shipping_status'].' '.$value['pay_status']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['sales_time']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['pay_time']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['shipping_time']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['consignee']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['tel']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['mobile']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['email']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['postscript']) . "\t";
            echo "\n";
        }
        exit;
    }
    $sale_list_data = get_sale_list();
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);

    make_json_result($smarty->fetch('sale_list.htm'), '', array('filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']));
}
/*------------------------------------------------------ */
//--��Ʒ��ϸ�б�
/*------------------------------------------------------ */
else
{
    /* Ȩ���ж� */
    admin_priv('sale_order_stats');
    /* ʱ����� */
    if (!isset($_REQUEST['start_date']))
    {
        $start_date = local_strtotime('-7 days');
    }
    if (!isset($_REQUEST['end_date']))
    {
        $end_date = local_strtotime('today');
    }
    
    $sale_list_data = get_sale_list();
    /* ��ֵ��ģ�� */
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('ur_here',          $_LANG['sell_stats']);
    $smarty->assign('full_page',        1);
    $smarty->assign('start_date',       local_date('Y-m-d', $start_date));
    $smarty->assign('end_date',         local_date('Y-m-d', $end_date));
    $smarty->assign('ur_here',      $_LANG['sale_list']);
    $smarty->assign('cfg_lang',     $_CFG['lang']);
    $smarty->assign('action_link',  array('text' => $_LANG['down_sales'],'href'=>'#download'));

    /* ��ʾҳ�� */
    assign_query_info();
    $smarty->display('sale_list.htm');
}
/*------------------------------------------------------ */
//--��ȡ������ϸ��Ҫ�ĺ���
/*------------------------------------------------------ */
/**
 * ȡ��������ϸ������Ϣ
 * @param   bool  $is_pagination  �Ƿ��ҳ
 * @return  array   ������ϸ����
 */
function get_sale_list($is_pagination = true){

    /* ʱ����� */
    $filter['start_date'] = empty($_REQUEST['start_date']) ? local_strtotime('-7 days') : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? local_strtotime('today') : local_strtotime($_REQUEST['end_date']);
  
    /* ��ѯ���ݵ����� */
    $where = " WHERE og.order_id = oi.order_id". order_query_sql('finished', 'oi.') .
             " AND oi.add_time >= '".$filter['start_date']."' AND oi.add_time < '" . ($filter['end_date'] + 86400) . "'";
    
    $sql = "SELECT COUNT(og.goods_id) FROM " .
           $GLOBALS['ecs']->table('order_info') . ' AS oi,'.
           $GLOBALS['ecs']->table('order_goods') . ' AS og '.
           $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* ��ҳ��С */
    $filter = page_and_size($filter);

    //NJPHP
    $sql = 'SELECT og.goods_id, og.goods_sn, og.goods_name, og.goods_number AS goods_num, og.goods_price '.
           'AS sales_price, oi.add_time AS sales_time, oi.order_id, oi.order_sn, '. 
           'oi.pay_name, oi.shipping_name, oi.invoice_no, oi.money_paid, oi.order_amount, oi.surplus, '.
           'oi.shipping_time, oi.pay_time, oi.order_status, oi.shipping_status, oi.pay_status, oi.postscript, oi.consignee, oi.tel, oi.mobile, oi.email '.
           "FROM " . $GLOBALS['ecs']->table('order_goods')." AS og, ".$GLOBALS['ecs']->table('order_info')." AS oi ".
           $where. " ORDER BY oi.order_id DESC";
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }

    $sale_list_data = $GLOBALS['db']->getAll($sql);

    foreach ($sale_list_data as $key => $item)
    {
        $sale_list_data[$key]['sales_price'] = price_format($sale_list_data[$key]['sales_price']);
        $sale_list_data[$key]['sales_time']  = local_date($GLOBALS['_CFG']['time_format'], $sale_list_data[$key]['sales_time']);
        
        //NJPHP
		$sale_list_data[$key]['money_paid']   = price_format($sale_list_data[$key]['money_paid']);
		$sale_list_data[$key]['order_amount'] = price_format($sale_list_data[$key]['order_amount']);
		$sale_list_data[$key]['surplus'] 	  = price_format($sale_list_data[$key]['surplus']);
        $sale_list_data[$key]['pay_time']		= local_date($GLOBALS['_CFG']['time_format'], $sale_list_data[$key]['pay_time']);
        $sale_list_data[$key]['shipping_time']  = local_date($GLOBALS['_CFG']['time_format'], $sale_list_data[$key]['shipping_time']);
        
    }
    $arr = array('sale_list_data' => $sale_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}
?>