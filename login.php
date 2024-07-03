<?php
// Oracle 데이터베이스 연결을 위한 TNS (Transparent Network Substrate) 설정
$tns = "(DESCRIPTION=
(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
(CONNECT_DATA= (SERVICE_NAME=XE)) )";

// PDO (PHP Data Objects) 데이터 소스 이름 (DSN)
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
// 데이터베이스 사용자명과 비밀번호
$username = 'd202202624';
$password = '328475';

try {
    // PDO 객체를 사용하여 데이터베이스 연결
    $conn = new PDO($dsn, $username, $password);
    // PDO 예외 모드 설정
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // POST 요청에서 고객 ID와 비밀번호를 가져옴
    $inputId = $_POST['customer_id'] ?? '';
    $inputPassword = $_POST['password'] ?? '';

    // 고객 인증을 위한 SQL 쿼리
    $query = "SELECT * FROM CUSTOMER WHERE CNO = :cno AND PASSWD = :passwd";

    // SQL 쿼리 준비 및 실행
    $stmt = $conn->prepare($query);
    $stmt->execute([':cno' => $inputId, ':passwd' => $inputPassword]);

    // 조회된 행의 수를 카운트
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $count++;

    // 고객 인증 성공 시
    if ($count > 0) {
        // 장바구니 (CART) 테이블에서 해당 고객의 주문이 완료되지 않은 항목이 있는지 확인
        $cartCheckQuery = "SELECT ID FROM CART WHERE CNO = :cno AND ORDERDATETIME IS NULL";
        $cartCheckStmt = $conn->prepare($cartCheckQuery);
        $cartCheckStmt->execute([':cno' => $inputId]);
        $cartId = $cartCheckStmt->fetchColumn();

        // 주문이 완료되지 않은 장바구니가 없으면 새 장바구니 생성
        if ($cartId === false) {
            // 새로운 장바구니 ID를 생성하기 위한 쿼리
            $idQuery = "SELECT 'CA' || LPAD(NVL(MAX(SUBSTR(ID, 3)), 0) + 1, 3, '0') FROM CART";
            $idStmt = $conn->prepare($idQuery);
            $idStmt->execute();
            $newId = $idStmt->fetchColumn();


            // 새로운 장바구니를 CART 테이블에 삽입
            $insertQuery = "INSERT INTO CART (ID, ORDERDATETIME, CNO) VALUES (:id, NULL, :cno)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->execute([':id' => $newId, ':cno' => $inputId]);

            // 새로운 장바구니 생성 성공 메시지 출력
            echo "New cart entry created with ID: $newId";
            $cartId = $newId;
        } else {
            // 이미 주문이 완료되지 않은 장바구니가 있는 경우 메시지 출력
            echo "Cart entry with ORDERDATETIME as NULL already exists for CNO: $inputId";
        }

        // 메인 페이지로 리다이렉트
        header("Location: mainpage.php?cno=" . $inputId . "&cartId=" . $cartId);
        exit;
    } else {
        // 로그인 실패 시 메시지 출력
        if ($inputId == '' && $inputPassword == '')
            echo "로그인 해주세요";
        else
            echo "회원번호 또는 패스워드가 잘못되었습니다.";
    }
} catch (PDOException $e) {
    // 예외 발생 시 에러 메시지 출력
    echo "에러 내용: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
<!-- 로그인 폼 -->
<form method="post" action="">
    <label for="customer_id">회원번호:</label>
    <input type="text" id="customer_id" name="customer_id" required>
    <br>
    <label for="password">패스워드:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <button type="submit">로그인</button>
</form>
</body>
</html>
