<?php
// 데이터베이스 연결 정보 설정
$tns = "(DESCRIPTION=
(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
(CONNECT_DATA= (SERVICE_NAME=XE)) )";
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
$username = 'd202202624';
$password = '328475';

// GET 파라미터에서 고객 번호와 날짜 범위 가져오기
$cno = $_GET['cno'];
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

// 데이터베이스 연결 시도
try {
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo("에러 내용: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문 내역</title>
    <!-- Bootstrap CSS 링크 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0"
          crossorigin="anonymous">
    <script>
        // 날짜 폼 제출 함수
        function submitForm(event) {
            event.preventDefault();

            var startDate = document.getElementById('startDate').value;
            var endDate = document.getElementById('endDate').value;

            var baseUrl = "order_history.php?cno=<?= $cno ?>";
            var newUrl = baseUrl + "&startDate=" + encodeURIComponent(startDate) + "&endDate=" + encodeURIComponent(endDate);

            window.location.href = newUrl;
        }
    </script>
</head>
<body>
<div class="container">
    <h2 class="text-center">주문 내역</h2>
    <!-- 날짜 범위 선택 폼 -->
    <form id="dateForm" onsubmit="submitForm(event)">
        <div class="row mb-3">
            <div class="col">
                <label for="startDate">시작 날짜:</label>
                <input type="date" id="startDate" name="startDate" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col">
                <label for="endDate">종료 날짜:</label>
                <input type="date" id="endDate" name="endDate" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
        </div>
        <div class="text-center mb-3">
            <button type="submit" class="btn btn-primary">조회</button>
        </div>
    </form>
    <!-- 주문 내역 테이블 -->
    <table class="table table-bordered text-center">
        <thead>
        <tr>
            <th>주문 날짜</th>
            <th>음식 이름</th>
            <th>수량</th>
            <th>총 가격</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // 주문 내역 조회 쿼리 작성
        $query = "
            SELECT CART.ORDERDATETIME, ORDERDETAIL.FOODNAME, ORDERDETAIL.QUANTITY, ORDERDETAIL.TOTALPRICE
            FROM CART
            JOIN ORDERDETAIL ON CART.ID = ORDERDETAIL.ID
            WHERE CART.CNO = :cno AND CART.ORDERDATETIME IS NOT NULL
        ";

        // 날짜 필터가 지정된 경우 쿼리에 추가
        if ($startDate && $endDate) {
            $query .= " AND CART.ORDERDATETIME BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')";
        }

        $query .= " ORDER BY CART.ORDERDATETIME DESC";

        // 쿼리 실행 준비
        $stmt = $conn->prepare($query);

        // 날짜 필터가 지정된 경우 바인드 파라미터 추가
        if ($startDate && $endDate) {
            $stmt->execute(array(':cno' => $cno, ':startDate' => $startDate, ':endDate' => $endDate));
        } else {
            $stmt->execute(array(':cno' => $cno));
        }

        // 결과 출력
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orderDateTime = htmlspecialchars($row['ORDERDATETIME']);
            $foodName = htmlspecialchars($row['FOODNAME']);
            $quantity = htmlspecialchars($row['QUANTITY']);
            $totalPrice = htmlspecialchars($row['TOTALPRICE']);
            ?>
            <tr>
                <td><?= $orderDateTime ?></td>
                <td><?= $foodName ?></td>
                <td><?= $quantity ?></td>
                <td><?= $totalPrice ?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>