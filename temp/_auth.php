<?php
/**
 * Autentizační funkce pro aplikaci CERTIFIO
 * Správa přihlášení, odhlášení a ověření uživatelů
 */

// Nutné načíst config.php
require_once 'config.php';

// Globální proměnná pro chybu přihlášení
$loginError = '';

/**
 * Zpracování přihlášení uživatele
 * @return bool True pokud byl uživatel úspěšně přihlášen
 */
function processLogin() {
    global $loginError;
    
    // Kontrola, zda již není uživatel přihlášen
    if (isUserLoggedIn()) {
        return true;
    }
    
    // Zpracování formuláře pro přihlášení
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_action'])) {
        $username = $_POST['username'];
        $enteredPassword = $_POST['password'];

        // Připojení k databázi
        $conn = connectToDatabase();

        if (!$conn) {
            $loginError = "Připojení k databázi SELIO selhalo";
            return false;
        }
        
        // Načtení dat uživatele z databáze
        $query = "SELECT login_id, MD5_hash, jmeno, prijmeni, active_ FROM Oil_LOG_LogData WHERE login_id = ? AND active_ = 1";
        $params = array($username);
        $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
        $result = sqlsrv_query($conn, $query, $params, $options);

        if ($result) {
            $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

            if ($row) {
                $storedHashedPassword = $row['MD5_hash'];
                $isActive = $row['active_'];

                // Ověření hesla
                if ($isActive == 1 && md5($enteredPassword) == $storedHashedPassword) {
                    // Platné přihlášení, uložení dat do session
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $row['jmeno'] . ' ' . $row['prijmeni'];
                    
                    // Přesměrování na stejnou stránku, aby se odstranila data z POST
                    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
                    header('Location: ' . $_SERVER['PHP_SELF'] . $queryString);
                    exit;
                } elseif ($isActive != 1) {
                    $loginError = 'Tento uživatel není aktivovaný';
                } else {
                    $loginError = 'Nesprávné heslo';
                }
            } else {
                $loginError = 'Uživatel nenalezen nebo není aktivovaný';
            }
        } else {
            $loginError = 'Dotaz na databázi selhal: ' . print_r(sqlsrv_errors(), true);
        }

        sqlsrv_close($conn);
        return false;
    }
    
    return false;
}

/**
 * Odhlášení uživatele
 */
function processLogout() {
    if (isset($_GET['logout'])) {
        // Zrušení session dat
        session_unset();
        session_destroy();
        
        // Přesměrování na hlavní stránku
        header('Location: index.php');
        exit;
    }
}

/**
 * Vrátí jméno přihlášeného uživatele
 * @return string Jméno a příjmení uživatele nebo prázdný řetězec
 */
function getUserFullName() {
    if (isUserLoggedIn()) {
        return $_SESSION['full_name'];
    }
    return '';
}

/**
 * Vrátí uživatelské jméno přihlášeného uživatele
 * @return string Uživatelské jméno nebo prázdný řetězec
 */
function getUsername() {
    if (isUserLoggedIn()) {
        return $_SESSION['username'];
    }
    return '';
}

// Zpracování odhlášení
processLogout();

// Zpracování přihlášení
processLogin();
?>
