<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

header('Content-Type: application/json');

$request = Application::getInstance()->getContext()->getRequest();


if (!$request->isPost() || !$request->isAjaxRequest()) {
    echo Json::encode(['status' => 'error', 'message' => 'Недопустимый метод запроса.']);
    die();
}

$elementId = (int)$request->getPost('id');

if ($elementId <= 0) {
    echo Json::encode(['status' => 'error', 'message' => 'Некорректный ID элемента.']);
    die();
}

if (!Loader::includeModule('iblock')) {
    echo Json::encode(['status' => 'error', 'message' => 'Ошибка сервера: модуль iblock не загружен.']);
    die();
}


$session = Application::getInstance()->getSession();

if (!$session->has('LIKED_ELEMENTS')) {
    $session->set('LIKED_ELEMENTS', []);
}
$likedElements = $session->get('LIKED_ELEMENTS');


$isLiked = in_array($elementId, $likedElements);

$dbElement = CIBlockElement::GetList(
    [], 
    ['ID' => $elementId, 'ACTIVE' => 'Y'], 
    false, 
    false, 
    ['ID', 'IBLOCK_ID', 'PROPERTY_LIKES_COUNT']
);

if ($element = $dbElement->Fetch()) {
    $iblockId = (int)$element['IBLOCK_ID'];
    $currentLikes = (int)$element['PROPERTY_LIKES_COUNT_VALUE'];
} else {
    echo Json::encode(['status' => 'error', 'message' => 'Элемент не найден или деактивирован.']);
    die();
}


if ($isLiked) {
    
    $newLikesCount = max(0, $currentLikes - 1);
    $likedElements = array_diff($likedElements, [$elementId]);
} else {

    $newLikesCount = $currentLikes + 1;
    $likedElements[] = $elementId;
}

CIBlockElement::SetPropertyValuesEx(
    $elementId, 
    $iblockId, 
    ['LIKES_COUNT' => $newLikesCount]
);

$taggedCache = Application::getInstance()->getTaggedCache();
$taggedCache->clearByTag('iblock_id_' . $iblockId);

$session->set('LIKED_ELEMENTS', $likedElements);

echo Json::encode([
    'status' => 'success',
    'is_liked' => !$isLiked, 
    'likes_count' => $newLikesCount
]);
die();