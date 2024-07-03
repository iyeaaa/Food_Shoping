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

    // POST로 전송된 데이터 받기
    $cno = $_POST['cno'];  // 고객 번호
    $foods = $_POST;  // 모든 POST 데이터 (음식 정보 포함)

    // 현재 고객의 활성 장바구니 ID 조회
    $stmt = $conn->prepare("SELECT ID FROM CART WHERE CNO = ? AND ORDERDATETIME IS NULL");
    $stmt->execute(array($cno));
    $cartId = $stmt->fetchColumn();

    // ORDERDETAIL 테이블의 ITEMNO 최대값 조회
    $stmt = $conn->prepare("SELECT MAX(ITEMNO) AS max_itemno FROM ORDERDETAIL");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemno = isset($result['MAX_ITEMNO']) ? $result['MAX_ITEMNO'] + 1 : 1;

    // 트랜잭션 시작
    $conn->beginTransaction();

    // POST 데이터 순회하며 음식 정보 처리
    foreach ($foods as $key => $quantity) {
        // 'food-quantity-'로 시작하는 키만 처리
        if (strpos($key, 'food-quantity-') === 0 && $quantity > 0) {
            $foodName = substr($key, strlen('food-quantity-'));  // 음식 이름 추출
            $price = $_POST["price-$foodName"];  // 해당 음식의 가격
            $totalPrice = $quantity * $price;  // 총 가격 계산

            // ORDERDETAIL 테이블에 주문 상세 정보 삽입
            $stmt = $conn->prepare("INSERT INTO ORDERDETAIL (ITEMNO, ID, QUANTITY, TOTALPRICE, FOODNAME) VALUES (:itemno, :id, :quantity, :totalprice, :foodname)");
            $stmt->execute(array(
                ':itemno' => $itemno,
                ':id' => $cartId,
                ':quantity' => $quantity,
                ':totalprice' => $totalPrice,
                ':foodname' => $foodName
            ));
            $itemno++;  // ITEMNO 증가
        }
    }

    // 트랜잭션 커밋
    $conn->commit();

    // 성공 메시지 출력 및 메인 페이지로 리다이렉트
    echo "<script>alert('음식이 장바구니에 추가되었습니다.'); window.location.href='mainpage.php?cno=$cno&cartId=$cartId&categoryName=$categoryName';</script>";
} catch (PDOException $e) {
    // 에러 발생 시 에러 메시지 출력
    echo "에러 내용: " . $e->getMessage();
}
?>