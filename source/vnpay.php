<?php
session_start();
error_reporting(0);
include('includes/config.php');

//get total price
$sql = "SELECT * FROM products WHERE id IN(";
foreach ($_SESSION['cart'] as $id => $value) {
	$sql .= $id . ",";
}
$sql = substr($sql, 0, -1) . ") ORDER BY id ASC";
$query = mysqli_query($con, $sql);
$totalprice = 0;
$totalqunty = 0;
if (!empty($query)) {
	while ($row = mysqli_fetch_array($query)) {
		$quantity = $_SESSION['cart'][$row['id']]['quantity'];
		$subtotal = $_SESSION['cart'][$row['id']]['quantity'] * $row['productPrice'] + $row['shippingCharge'];
		$totalprice += $subtotal;
		$_SESSION['qnty'] = $totalqunty += $quantity;
	}
}
//Lay thong tin dia chi khach hang
$sql = "select * from users where id= " . $_SESSION['id'];
$result = mysqli_query($con, $sql);
$data = mysqli_fetch_array($result, MYSQLI_ASSOC);
//print_r($data);

//Phan code tich hop vnpay
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "https://phuongdeptrai.tk/vnpay_return.php";
$vnp_TmnCode = "KCBYK0UR"; //Mã website tại VNPAY 
$vnp_HashSecret = "YWAWQFBBLYOPPGBKKACZATILEXGNYGDB"; //Chuỗi bí mật
//Goi url thanh toan
//Lay orderID
$sql = "select MAX(id) as order_id from  orders where userId= " . $_SESSION['id'];
$result = mysqli_query($con, $sql);
$order_id = mysqli_fetch_array($result, MYSQLI_ASSOC)['order_id'];
//Khai bao cac tham so
$vnp_TxnRef = $order_id;
$vnp_OrderInfo = "UNO Store - Bill Payment" . $order_id;
$vnp_OrderType = "billpayment";
$vnp_Amount = $totalprice * 25000;
$vnp_Locale = "vn";
$vnp_BankCode = "";
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];


$inputData = array(
	"vnp_Version" => "2.0.0",
	"vnp_TmnCode" => $vnp_TmnCode,
	"vnp_Amount" => $vnp_Amount,
	"vnp_Command" => "pay",
	"vnp_CreateDate" => date('YmdHis'),
	"vnp_CurrCode" => "VND",
	"vnp_IpAddr" => $vnp_IpAddr,
	"vnp_Locale" => $vnp_Locale,
	"vnp_OrderInfo" => $vnp_OrderInfo,
	"vnp_OrderType" => $vnp_OrderType,
	"vnp_ReturnUrl" => $vnp_Returnurl,
	"vnp_TxnRef" => $vnp_TxnRef,
);

if (isset($vnp_BankCode) && $vnp_BankCode != "") {
	$inputData['vnp_BankCode'] = $vnp_BankCode;
}

ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
	if ($i == 1) {
		$hashdata .= '&' . $key . "=" . $value;
	} else {
		$hashdata .= $key . "=" . $value;
		$i = 1;
	}
	$query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_Url = $vnp_Url . "?" . $query;
if (isset($vnp_HashSecret)) {
	// $vnpSecureHash = md5($vnp_HashSecret . $hashdata);
	//echo $hashdata;
	$vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
	$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
}
//echo $vnp_Url;
header("Location: $vnp_Url");
