<?php
// 데이터베이스 연결 정보 설정
$tns = "(DESCRIPTION=
(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
(CONNECT_DATA= (SERVICE_NAME=XE)) )";
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
$username = 'd202202624';
$password = '328475';

try {
    // 데이터베이스 연결
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cno = $_GET['cno'];

    // POST 요청 처리 (결제 로직)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $currentDateTime = date('Y-m-d H:i:s');

        // 현재 장바구니의 ORDERDATETIME 업데이트 (결제 완료 처리)
        $updateQuery = "UPDATE CART 
                        SET ORDERDATETIME = :currentDateTime 
                        WHERE CNO = :cno AND ORDERDATETIME IS NULL";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute(array(':currentDateTime' => $currentDateTime, ':cno' => $cno));

        // 새로운 장바구니 ID 생성
        $idQuery = "SELECT 'CA' || LPAD(NVL(MAX(SUBSTR(ID, 3)), 0) + 1, 3, '0') FROM CART";
        $idStmt = $conn->prepare($idQuery);
        $idStmt->execute();
        $newId = $idStmt->fetchColumn();

        echo "New Cart ID: " . $newId . "\n";

        // 새로운 장바구니 생성
        $insertQuery = "INSERT INTO CART (ID, CNO, ORDERDATETIME) VALUES (:newId, :cno, NULL)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute(array(':newId' => $newId, ':cno' => $cno));

        echo "<script>alert('결제가 완료되었습니다. 새로운 장바구니가 생성되었습니다.');</script>";
    }

    // 현재 장바구니 내용 조회
    $query = "SELECT O.ITEMNO, O.ID, O.QUANTITY, O.TOTALPRICE, O.FOODNAME 
              FROM CART C, ORDERDETAIL O
              WHERE C.ID = O.ID
              AND C.CNO = :cno
              AND C.ORDERDATETIME IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute(array(':cno' => $cno));
} catch (PDOException $e) {
    echo "에러 내용: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0"
          crossorigin="anonymous">
    <style> a { text-decoration: none; } </style>
    <title>장바구니</title>
</head>
<body>
    <h1 class="text-center">장바구니</h1>
    <!-- 장바구니 내용 테이블 -->
    <table class="table table-bordered text-center">
        <thead>
            <tr>
                <th>No.</th>
                <th>품목</th>
                <th>수량</th>
                <th>총 금액</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $itemNo = $row['ITEMNO'];
                    $id = $row['ID'];
                    $quantity = $row['QUANTITY'];
                    $totalPrice = $row['TOTALPRICE'];
                    $foodName = $row['FOODNAME'];
                    $total += $totalPrice;
            ?>
            <tr>
                <td><?= $itemNo ?></td>
                <td><?= $foodName ?></td>
                <td><?= $quantity ?></td>
                <td><?= $totalPrice ?></td>
            </tr>
            <?php
                }
            } ?>
        </tbody>
    </table>
    <div>총 금액: <?= $total ?></div>
    <!-- 결제 버튼 -->
    <form method="post">
        <button type="submit" class="btn btn-primary">결제하기</button>
    </form>
</body>
</html>