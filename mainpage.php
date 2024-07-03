<?php
$tns = "(DESCRIPTION=
(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
(CONNECT_DATA= (SERVICE_NAME=XE)) )";
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
$username = 'd202202624';
$password = '328475';

$cno = $_GET['cno'];
$categoryName = $_GET['categoryName'] ?? '%%';
$cartId = $_GET['cartId'];
$searchWord = $_GET['searchWord'] ?? '';
$minValue = $_GET['minValue'] ?? 0;
$maxValue = $_GET['maxValue'] ?? PHP_INT_MAX;

print("cno: " . $cno . "\n");
if ($minValue === '') $minValue = 0;
if ($maxValue === '') $maxValue = PHP_INT_MAX;

try {
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo("에러 내용: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">

<!-- Header -->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0"
          crossorigin="anonymous">
    <style> a {
            text-decoration: none;
        } </style>
    <title>밥집</title>
    <script>
        function updateTotal(changedInput) {
            let checkboxes = document.querySelectorAll('input[name^="food-quantity-"]');
            let total = 0;
            let selectedCount = 0;

            checkboxes.forEach((checkbox) => {
                let quantity = parseInt(checkbox.value) || 0;
                let price = parseInt(checkbox.dataset.price);
                let foodPrice = quantity * price;
                total += foodPrice;
                selectedCount += quantity;

                let foodPriceDiv = document.getElementById(`food-price-${checkbox.name.split('-')[2]}`);
                foodPriceDiv.textContent = `${foodPrice}`;
            });

            document.getElementById('totalPrice').innerText = 'Total Price: ' + total + ' (Selected: ' + selectedCount + ')';
        }

        function submitForm(event) {
            event.preventDefault(); // 폼의 기본 제출을 방지

            // 입력된 값 가져오기
            var searchWord = document.getElementById('searchWord').value;
            var minValue = document.getElementById('minValue').value;
            var maxValue = document.getElementById('maxValue').value;

            // 기본 URL 설정
            var baseUrl = "http://localhost/shop/mainpage.php?cno=<?=$cno?>&cartId=<?=$cartId?>&categoryName=<?=$categoryName?>";

            // URL에 파라미터 추가
            var newUrl = baseUrl + "&searchWord=" + encodeURIComponent(searchWord) + "&minValue=" + encodeURIComponent(minValue) + "&maxValue=" + encodeURIComponent(maxValue);

            // 새로운 URL로 이동
            window.location.href = newUrl;
        }

        function changeCategory(category) {
            var searchWord = document.getElementById('searchWord').value;
            var minValue = document.getElementById('minValue').value;
            var maxValue = document.getElementById('maxValue').value;

            var baseUrl = "http://localhost/shop/mainpage.php?cno=<?=$cno?>&cartId=<?=$cartId?>";

            var newUrl = baseUrl + "&categoryName=" + encodeURIComponent(category) +
                "&searchWord=" + encodeURIComponent(searchWord) +
                "&minValue=" + encodeURIComponent(minValue) +
                "&maxValue=" + encodeURIComponent(maxValue);

            window.location.href = newUrl;
        }
    </script>
</head>
<body>

<div class="container">
    <h2 class="text-center">MainPage</h2>

    <!-- 카테고리 버튼 -->
    <div class="text-center mb-3">
        <button class="btn btn-outline-danger" onclick="changeCategory('%%')">Total</button>
        <button class="btn btn-outline-primary" onclick="changeCategory('Korean')">Korean</button>
        <button class="btn btn-outline-secondary" onclick="changeCategory('Chinese')">Chinese</button>
        <button class="btn btn-outline-success" onclick="changeCategory('Japanese')">Japanese</button>
        <button class="btn btn-outline-danger" onclick="changeCategory('Western')">Western</button>
    </div>

    <!-- 검색 -->
    <form id="searchForm" onsubmit="submitForm(event)">
        <label for="searchWord">Search Word:</label>
        <input type="text" id="searchWord" name="searchWord" value="<?= htmlspecialchars($searchWord) ?>"><br><br>

        <label for="minValue">Minimum Value:</label>
        <input type="text" id="minValue" name="minValue"><br><br>

        <label for="maxValue">Maximum Value:</label>
        <input type="text" id="maxValue" name="maxValue"><br><br>

        <button type="submit" class="btn btn-primary mb-3">Search</button>
    </form>

    <!-- 합계 계산 -->
    <div id="totalPrice" class="text-center mt-3">Total Price: 0 (Selected: 0)</div><br>

    <!-- 카테고리 리스트 -->
    <form method="post" action="add_to_cart.php">
        <table class="table table-bordered text-center">
            <thead>
            <tr>
                <th>카테고리</th>
                <th>음식</th>
                <th>가격</th>
                <th>수량</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $query = "SELECT C.CATEGORYNAME, F.FOODNAME, F.PRICE FROM CONTAIN C, FOOD F 
                    WHERE C.FOODNAME = F.FOODNAME 
                    AND (LOWER(C.FOODNAME) LIKE '%' || :searchWord || '%')
                    AND F.PRICE BETWEEN :minValue and :maxValue
                    AND C.CATEGORYNAME LIKE :categoryName";

            $stmt = $conn->prepare($query);
            $stmt->execute(array(
                ':searchWord' => $searchWord,
                ':minValue' => $minValue,
                ':maxValue' => $maxValue,
                ':categoryName' => $categoryName
            ));

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $foodName = htmlspecialchars($row['FOODNAME']);
                $categoryName = htmlspecialchars($row['CATEGORYNAME']);
                $price = htmlspecialchars($row['PRICE']);
                ?>
                <tr>
                    <td><?= $categoryName ?></td>
                    <td><?= $foodName ?></td>
                    <td><?= $price ?></td>
                    <td>
                        <input type="number" name="food-quantity-<?= $foodName ?>" data-price="<?= $price ?>" min="0"
                               value="0" onchange="updateTotal(this)">
                        <input type="hidden" name="price-<?= $foodName ?>" value="<?= $price ?>">
                        <div id="food-price-<?= $foodName ?>">0</div>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>

        <!-- cno hidden input 필드 추가 -->
        <input type="hidden" name="cno" value="<?= $cno ?>">
        <input type="hidden" name="cartId" value="<?= $cartId ?>">

        <!-- 장바구니에 담기 버튼 -->
        <div class="text-center mt-3">
            <button type="submit" class="btn btn-success">장바구니에 담기</button>
        </div>
    </form>

    <!-- 장바구니로 이동하는 버튼 추가 -->
    <div class="text-center mt-3">
        <a href="cart.php?cno=<?= $cno ?>" class="btn btn-primary">장바구니 보기</a>
    </div>

    <!-- 주문내역으로 이동하는 버튼 추가 -->
    <div class="text-center mt-3">
        <a href="order_history.php?cno=<?= $cno ?>" class="btn btn-info">주문내역 보기</a>
    </div>

    <!-- 통계정보로 이동하는 버튼 추가 (cno가 'c0'일 경우만) -->
    <?php if ($cno === 'c0') { ?>
        <div class="text-center mt-3">
            <a href="statistics.php" class="btn btn-warning">통계 정보 보기</a>
        </div>
    <?php } ?>
</div>
</body>
</html>
