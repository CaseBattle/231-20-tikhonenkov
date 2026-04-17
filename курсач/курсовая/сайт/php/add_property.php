<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $rooms = (int) ($_POST['rooms'] ?? 0);
    $area = trim($_POST['area'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $paymentType = trim($_POST['payment_type'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($image === '') {
        $image = 'images/demo-property.jpg';
    }

    if (
        $title !== '' &&
        $address !== '' &&
        $district !== '' &&
        $price > 0 &&
        in_array($type, ['rent', 'sale'], true) &&
        $rooms > 0 &&
        $area !== '' &&
        $floor !== '' &&
        $phone !== '' &&
        in_array($paymentType, ['cash', 'online', 'both'], true) &&
        in_array($status, ['available', 'reserved', 'rented', 'sold'], true)
    ) {
        $stmt = getPDO()->prepare(
            'INSERT INTO properties(title, address, price, type, description, rooms, area, floor, district, phone, payment_type, image, status) 
             VALUES(:title, :address, :price, :type, :description, :rooms, :area, :floor, :district, :phone, :payment_type, :image, :status)'
        );
        $stmt->execute([
            ':title' => $title,
            ':address' => $address,
            ':price' => $price,
            ':type' => $type,
            ':description' => $description,
            ':rooms' => $rooms,
            ':area' => $area,
            ':floor' => $floor,
            ':district' => $district,
            ':phone' => $phone,
            ':payment_type' => $paymentType,
            ':image' => $image,
            ':status' => $status,
        ]);
    }
}

header('Location: admin_properties.php');
exit;

