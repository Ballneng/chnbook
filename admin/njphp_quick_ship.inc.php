<?php
if($_REQUEST['op'] == 'njphp_ship'){

	//ȷ�϶�����ȷ�Ϸֵ���ȷ�Ϸ�����ȷ��ʱ�䡢����ʱ�䡢��������
	$njphp_time		  = gmtime();
	$njphp_order_id   = intval($_REQUEST['njphp_order_id']);
	$njphp_invoice_no = trim($_REQUEST['njphp_invoice_no']);
	$njphp_to_buyer   = $_REQUEST['njphp_to_buyer'];
	
	$sql = "
		UPDATE 
		ecs_order_info 
		SET 
		order_status	  = '1',
		shipping_status   = '1',
		confirm_time 	  = '$njphp_time',
		shipping_time     = '$njphp_time',
		invoice_no	  	  = '$njphp_invoice_no',
		is_separate 	  = '1',
		to_buyer		  = '$njphp_to_buyer'
		WHERE 
		order_id		  = '$njphp_order_id';
	";
	
	//echo $sql;exit;
	
	$db->query($sql);

    include_once(ROOT_PATH . 'includes/lib_clips.php');
	//2012-09-25 qianlei ֻҪ����״̬��Ϊ�ѷ����������û����Ա���������û�������Ϣ
	if ($order = order_info($njphp_order_id))
	{
        //��ʱ�ر��������
        $old_check = $GLOBALS['_CFG']['message_check'];
        $GLOBALS['_CFG']['message_check'] = 0;
		$content = "���ã��������Ķ�����֧���ɹ����̲Ľ���{date}���簲�ŷ����������������ڽ̲����ĵĶ�����ѯ�в�ѯ���Ŀ�ݵ��ţ�
                ���ڿ�ݵ�������36Сʱ���ѯ�����������������ջ�ǰ���ֵ绰��ͨ����л���Թ���Ա�̲����ĵ��������ע��";

        $content = str_replace(array('{date}'),array(date('m��d��',$order['shipping_time'])),$content);
		$message = array(
			'user_id'     => 9999999,
			'user_name'   => '�����û�',
			'user_email'  => '',
			'msg_type'    => 0,
			'msg_title'   => '������ѯ',
			'msg_content' =>get_order_message_content($order) ,
			'order_id'    => $order['order_id'],
			'msg_area'    => 1,
			'upload'      => array()
		 );
		add_message($message);

        $sql = "INSERT INTO ".$GLOBALS['ecs']->table('feedback')." (msg_title, msg_time, user_id, user_name , ".
            "user_email, parent_id, msg_content) ".
            "VALUES ('reply', '".gmtime()."', '4', 'jzx', '277418588@qq.com', ".
            "'".$GLOBALS['db']->insert_id()."', '".$content."') ";
        $GLOBALS['db']->query($sql);

        $GLOBALS['_CFG']['message_check'] =  $old_check;
	}
	
	
	
	//��ת��������Ϣҳ��
	ecs_header("Location: order.php?act=list\n");

}else{

	/* ���ݶ���id�򶩵��Ų�ѯ������Ϣ */
	if (isset($_REQUEST['order_id']))
	{
		$order_id = intval($_REQUEST['order_id']);
		$order = order_info($order_id);
	}
	elseif (isset($_REQUEST['order_sn']))
	{
		$order_sn = trim($_REQUEST['order_sn']);
		$order = order_info(0, $order_sn);
	}
	else
	{
		/* ������������ڣ��˳� */
		die('invalid parameter');
	}
	
	/* ������������ڣ��˳� */
	if (empty($order))
	{
		die('order does not exist');
	}
	
	/* ���ݶ����Ƿ���ɼ��Ȩ�� */
	if (order_finished($order))
	{
		admin_priv('order_view_finished');
	}
	else
	{
		admin_priv('order_view');
	}
	
	/* ȡ����һ������һ�������� */
	if (!empty($_COOKIE['ECSCP']['lastfilter']))
	{
		$filter = unserialize(urldecode($_COOKIE['ECSCP']['lastfilter']));
		if (!empty($filter['composite_status']))
		{
			$where = '';
			//�ۺ�״̬
			switch($filter['composite_status'])
			{
				case CS_AWAIT_PAY :
					$where .= order_query_sql('await_pay');
					break;

				case CS_AWAIT_SHIP :
					$where .= order_query_sql('await_ship');
					break;

				case CS_FINISHED :
					$where .= order_query_sql('finished');
					break;

				default:
					if ($filter['composite_status'] != -1)
					{
						$where .= " AND o.order_status = '$filter[composite_status]' ";
					}
			}
		}
	}

	$sql = "SELECT MAX(order_id) FROM " . $ecs->table('order_info') . " as o WHERE order_id < '$order[order_id]'";
	if ($agency_id > 0)
	{
		$sql .= " AND agency_id = '$agency_id'";
	}
	if (!empty($where))
	{
		$sql .= $where;
	}
	$smarty->assign('prev_id', $db->getOne($sql));
	$sql = "SELECT MIN(order_id) FROM " . $ecs->table('order_info') . " as o WHERE order_id > '$order[order_id]'";
	if ($agency_id > 0)
	{
		$sql .= " AND agency_id = '$agency_id'";
	}
	if (!empty($where))
	{
		$sql .= $where;
	}
	$smarty->assign('next_id', $db->getOne($sql));

	/* ȡ���û��� */
	if ($order['user_id'] > 0)
	{
		$user = user_info($order['user_id']);
		if (!empty($user))
		{
			$order['user_name'] = $user['user_name'];
		}
	}

	/* ȡ�����а��´� */
	$sql = "SELECT agency_id, agency_name FROM " . $ecs->table('agency');
	$smarty->assign('agency_list', $db->getAll($sql));

	/* ȡ�������� */
	$sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
	"'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
	"FROM " . $ecs->table('order_info') . " AS o " .
	"LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
	"LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
	"LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
	"LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
	"WHERE o.order_id = '$order[order_id]'";
	$order['region'] = $db->getOne($sql);

	/* ��ʽ����� */
	if ($order['order_amount'] < 0)
	{
		$order['money_refund']          = abs($order['order_amount']);
		$order['formated_money_refund'] = price_format(abs($order['order_amount']));
	}

	/* �������� */
	$order['order_time']    = local_date($_CFG['time_format'], $order['add_time']);
	$order['pay_time']      = $order['pay_time'] > 0 ?
	local_date($_CFG['time_format'], $order['pay_time']) : $_LANG['ps'][PS_UNPAYED];
	$order['shipping_time'] = $order['shipping_time'] > 0 ?
	local_date($_CFG['time_format'], $order['shipping_time']) : $_LANG['ss'][SS_UNSHIPPED];
	$order['status']        = $_LANG['os'][$order['order_status']] . ',' . $_LANG['ps'][$order['pay_status']] . ',' . $_LANG['ss'][$order['shipping_status']];
	$order['invoice_no']    = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];

	/* ȡ�ö�������Դ */
	if ($order['from_ad'] == 0)
	{
		$order['referer'] = empty($order['referer']) ? $_LANG['from_self_site'] : $order['referer'];
	}
	elseif ($order['from_ad'] == -1)
	{
		$order['referer'] = $_LANG['from_goods_js'] . ' ('.$_LANG['from'] . $order['referer'].')';
	}
	else
	{
		/* ��ѯ�������� */
		$ad_name = $db->getOne("SELECT ad_name FROM " .$ecs->table('ad'). " WHERE ad_id='$order[from_ad]'");
		$order['referer'] = $_LANG['from_ad_js'] . $ad_name . ' ('.$_LANG['from'] . $order['referer'].')';
	}

	/* �˶����ķ�����ע(�˶��������һ��������¼) */
	$sql = "SELECT action_note FROM " . $ecs->table('order_action').
	" WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY log_time DESC";
	$order['invoice_note'] = $db->getOne($sql);

	/* ȡ�ö�����Ʒ������ */
	$weight_price = order_weight_price($order['order_id']);
	$order['total_weight'] = $weight_price['formated_weight'];

	/* ������ֵ������ */
	$smarty->assign('order', $order);

	/* ȡ���û���Ϣ */
	if ($order['user_id'] > 0)
	{
		/* �û��ȼ� */
		if ($user['user_rank'] > 0)
		{
			$where = " WHERE rank_id = '$user[user_rank]' ";
		}
		else
		{
			$where = " WHERE min_points <= " . intval($user['rank_points']) . " ORDER BY min_points DESC ";
		}
		$sql = "SELECT rank_name FROM " . $ecs->table('user_rank') . $where;
		$user['rank_name'] = $db->getOne($sql);

		// �û��������
		$day    = getdate();
		$today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
		$sql = "SELECT COUNT(*) " .
		"FROM " . $ecs->table('bonus_type') . " AS bt, " . $ecs->table('user_bonus') . " AS ub " .
		"WHERE bt.type_id = ub.bonus_type_id " .
		"AND ub.user_id = '$order[user_id]' " .
		"AND ub.order_id = 0 " .
		"AND bt.use_start_date <= '$today' " .
		"AND bt.use_end_date >= '$today'";
		$user['bonus_count'] = $db->getOne($sql);
		$smarty->assign('user', $user);

		// ��ַ��Ϣ
		$sql = "SELECT * FROM " . $ecs->table('user_address') . " WHERE user_id = '$order[user_id]'";
		$smarty->assign('address_list', $db->getAll($sql));
	}

	/* ȡ�ö�����Ʒ����Ʒ */
	$goods_list = array();
	$goods_attr = array();
	$sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn
            FROM " . $ecs->table('order_goods') . " AS o
                LEFT JOIN " . $ecs->table('products') . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $ecs->table('goods') . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $ecs->table('brand') . " AS b
                    ON g.brand_id = b.brand_id
            WHERE o.order_id = '$order[order_id]'";
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res))
	{
		/* ������Ʒ֧�� */
		if ($row['is_real'] == 0)
		{
			/* ȡ�������� */
			$filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
			if (file_exists($filename))
			{
				include_once($filename);
				if (!empty($_LANG[$row['extension_code'].'_link']))
				{
					$row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
				}
			}
		}

		$row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
		$row['formated_goods_price']    = price_format($row['goods_price']);

		$goods_attr[] = explode(' ', trim($row['goods_attr'])); //����Ʒ���Բ��Ϊһ������

		if ($row['extension_code'] == 'package_buy')
		{
			$row['storage'] = '';
			$row['brand_name'] = '';
			$row['package_goods_list'] = get_package_goods($row['goods_id']);
		}

		$goods_list[] = $row;
	}

	$attr = array();
	$arr  = array();
	foreach ($goods_attr AS $index => $array_val)
	{
		foreach ($array_val AS $value)
		{
			$arr = explode(':', $value);//�� : �Ž����Բ�
			$attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
		}
	}

	$smarty->assign('goods_attr', $attr);
	$smarty->assign('goods_list', $goods_list);

	/* ȡ����ִ�еĲ����б� */
	$operable_list = operable_list($order);
	$smarty->assign('operable_list', $operable_list);

	/* ȡ�ö���������¼ */
	$act_list = array();
	$sql = "SELECT * FROM " . $ecs->table('order_action') . " WHERE order_id = '$order[order_id]' ORDER BY log_time DESC,action_id DESC";
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res))
	{
		$row['order_status']    = $_LANG['os'][$row['order_status']];
		$row['pay_status']      = $_LANG['ps'][$row['pay_status']];
		$row['shipping_status'] = $_LANG['ss'][$row['shipping_status']];
		$row['action_time']     = local_date($_CFG['time_format'], $row['log_time']);
		$act_list[] = $row;
	}
	$smarty->assign('action_list', $act_list);

	/* ȡ���Ƿ����ʵ����Ʒ */
	$smarty->assign('exist_real_goods', exist_real_goods($order['order_id']));
}

function get_order_message_content($order)
{
    $message_title = array(
        1=>'���ã����ѳɹ�����ҵĶ�������{order_sn}������ʲôʱ���ܷ�����',
        2=>'�ҵĶ�������{order_sn}���Ѹ���뾡�췢��лл',
        3=>'�ҵĶ�������{order_sn} ������� ʲôʱ����Է���',
        4=>'�ҵĶ�����{order_sn}�Ѹ���뾡�췢����лл��',
        5=>'������{order_sn} ������� ���ʲôʱ�򷢻� ʲôʱ���ܵ���',
        6=>'��ã��Ҷ����ţ�{order_sn}�����ʺ�ʱ����������ô֪�����������',
    );

    return str_replace(array('{order_sn}'),array($order['order_sn']),$message_title[array_rand($message_title,1)]);
}
?>