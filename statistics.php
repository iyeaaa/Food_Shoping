<?php
// 데이터베이스 연결 정보 설정
$tns = "(DESCRIPTION=
(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
(CONNECT_DATA= (SERVICE_NAME=XE)) )";
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
$username = 'd202202624';
$password = '328475';

// 데이터베이스 연결 시도
try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // 예외 처리 설정
} catch (PDOException $e) {
    echo("에러 내용: " . $e->getMessage());
    exit();
}

// 총 매출액을 조회하는 함수 (윈도우 함수 사용)
function getTotalSalesWithWindow($conn) {
    $query = "SELECT SUM(TOTALPRICE) OVER () AS TOTALSALES FROM ORDERDETAIL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['TOTALSALES'];
}

// 고객별 주문 건수를 조회하는 함수 (RANK() 함수 사용)
function getCustomerOrderCount($conn) {
    $query = "SELECT CUSTOMER.NAME, COUNT(*) AS ORDERCOUNT,
                     RANK() OVER (ORDER BY COUNT(*) DESC) AS RANK
              FROM CART
              JOIN CUSTOMER ON CART.CNO = CUSTOMER.CNO
              GROUP BY CUSTOMER.NAME";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 매출 통계
function getCategorySalesWithRollup($conn) {
    $query = "SELECT 
                  COALESCE(CONTAIN.CATEGORYNAME, '전체') AS CATEGORYNAME,
                  COALESCE(ORDERDETAIL.FOODNAME, '전체') AS FOODNAME,
                  SUM(ORDERDETAIL.QUANTITY) AS TOTALQUANTITY,
                  SUM(ORDERDETAIL.TOTALPRICE) AS TOTALSALES
              FROM ORDERDETAIL
              JOIN CONTAIN ON ORDERDETAIL.FOODNAME = CONTAIN.FOODNAME
              GROUP BY ROLLUP(CONTAIN.CATEGORYNAME, ORDERDETAIL.FOODNAME)
              ORDER BY CONTAIN.CATEGORYNAME ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>통계 정보</title>
    <!-- Bootstrap CSS 링크 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h2 class="text-center">통계 정보</h2>

    <!-- 총 매출액 표시 -->
    <div class="mt-3">
        <h4>총 매출액</h4>
        <p><?= getTotalSalesWithWindow($conn) ?> 원</p>
    </div>

    <!-- 고객별 주문 건수 표시 -->
    <div class="mt-3">
        <h4>고객별 주문 건수</h4>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>순위</th>
                <th>고객명</th>
                <th>주문 건수</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $customerOrders = getCustomerOrderCount($conn);
            foreach ($customerOrders as $order) {
                echo "<tr><td>" . htmlspecialchars($order['RANK']) . "</td><td>" . htmlspecialchars($order['NAME']) . "</td><td>" . htmlspecialchars($order['ORDERCOUNT']) . "</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <!-- 카테고리별 매출 표시 (ROLLUP 사용) -->
    <div class="mt-3">
        <h4>매출 분류 통계</h4>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>카테고리명</th>
                <th>음식명</th>
                <th>총 판매량</th>
                <th>총 매출액</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $categorySales = getCategorySalesWithRollup($conn);
            foreach ($categorySales as $sale) {
                echo "<tr><td>" . htmlspecialchars($sale['CATEGORYNAME']) . "</td><td>" . htmlspecialchars($sale['FOODNAME']) . "</td><td>" . htmlspecialchars($sale['TOTALQUANTITY']) . "</td><td>" . htmlspecialchars($sale['TOTALSALES']) . " 원</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
