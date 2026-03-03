<?php
/**
 * admin/_auth.php
 * 관리자 페이지 상단에서 require 해서 사용
 * → 비로그인 또는 admin이 아닌 경우 로그인 페이지로 리다이렉트
 */
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: /chunking-english/login.php');
    exit;
}
