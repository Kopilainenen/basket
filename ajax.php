<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die(); ?>
<?
$arItemsID = array();
$arSectionsID = array();
$arSections = array();

foreach ($arResult["CATEGORIES"] as $category) {
    foreach ($category['ITEMS'] as $item) {
        if (isset($item['ITEM_ID'])) {
            $arItemsID[] = $item['ITEM_ID'];
        }
    }
}

$arResult['MEASURE'] = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($arItemsID); 

global $TTIblocks;
if (!empty($arItemsID)) {
    $rsItems = CIBlockElement::GetList(array(), array('ID' => $arItemsID, 'SITE_ID' => SITE_ID, 'IBLOCK_ID' => $TTIblocks["CATALOG_PRODUCT"]), false, false, array('ID', 'IBLOCK_SECTION_ID', 'PROPERTY_OLD_PRICE', "PROPERTY_CART_PRICE", "PROPERTY_PRICE_CARD", "PROPERTY_LOCAL_MANUFACT"));

    while ($item = $rsItems->GetNext()) {
        $arSectionsID[$item['ID']] = $item['IBLOCK_SECTION_ID'];
        $arResult["ELEMENTS"][$item["ID"]]['OLD_PRICE'] = $item['PROPERTY_OLD_PRICE_VALUE'] > 0 ? true : false;
        $arResult["ELEMENTS"][$item["ID"]]['CART_PRICE'] = $item['PROPERTY_CART_PRICE_VALUE'] > 0 ? true : false;
        $arResult["ELEMENTS"][$item["ID"]]['PRICE_CARD'] = $item['PROPERTY_PRICE_CARD_VALUE'] > 0 ? true : false;
        $arResult["ELEMENTS"][$item["ID"]]['PRICES']['CARD']['VALUE'] = $item['PROPERTY_PRICE_CARD_VALUE'];
        $arResult["ELEMENTS"][$item["ID"]]['PRICES']['DISCOUNT']['VALUE'] = $item['PROPERTY_CART_PRICE_VALUE'];
        $arResult["ELEMENTS"][$item["ID"]]['LOCAL_MANUFACT'] = $item['PROPERTY_LOCAL_MANUFACT_VALUE'] > 0 ? true : false;
        $itms[] = $item;
    }

    $secID = array_filter($arSectionsID);
    $secID = array_unique($secID);
}
if (!empty($secID)) {
    $rsSections = CIBlockSection::GetList(array(), array('ID' => $secID), false, array('ID', 'NAME', 'SECTION_PAGE_URL'));

    while ($section = $rsSections->GetNext()) {
        $arSections[$section['ID']] = $section;
    }
}
//print_var($arResult["CATEGORIES"], true);

//if($USER->isAdmin())
  //echo '<pre>'; print_r($arResult); echo '</pre>';

$all = $arResult["CATEGORIES"]['all'];

unset($arResult["CATEGORIES"]['all']);
$date = new DateTime(date('d.m.Y 18:00:00'));
$isDate = date('w') == 1 && $date->getTimestamp() < time();
?>

<? if (!empty($arResult["CATEGORIES"])): ?>
    <div class="search-baloon js-input-change-baloon active">
        <? foreach ($arResult["CATEGORIES"] as $category_id => $arCategory):
            ?>
            <?
            foreach ($arCategory["ITEMS"] as $j => $arItem):
                if ($arItem["ITEM_ID"] == 0)
                    continue;

                $arElement = $arResult["ELEMENTS"][$arItem["ITEM_ID"]];
                if ($arElement["XML_ID"] == CBasketActions::PACKAGE)
                    continue;
                $itemID = $arItem['ITEM_ID'];
                $sectionID = $arSectionsID[$itemID];
                $section = $arSections[$sectionID];
                $catalog = \Bitrix\Catalog\Model\Product::getList(['filter' => ['ID' => $arItem['ITEM_ID']]])->fetch();
	            $arElement['CATALOG_QUANTITY'] = $catalog['QUANTITY'];
                list($canBuy, $title) = CSiteUtils::checkQuantity($arElement);

                $props = CIBlockElement::GetProperty($arElement['IBLOCK_ID'], $arElement['ID'], 'id', 'desc', ['CODE' => 'weight'])->Fetch()['VALUE_ENUM'];
                $measureTitle = $arResult['MEASURE'][$arElement['ID']]['MEASURE']['SYMBOL'];
                

                //$measure = $props ? '0.1' : 1;
                $measure = $arResult['MEASURE'][$arElement['ID']]['RATIO'];
                if ($arElement['IN_BASKET'])
                    $measure = floatval($arElement['BASKET_COUNT']);
                $unit = $props ? 'weight' : 'piece';
                ?>

                <div class="search-baloon-row js-good-item <?= $arElement['IN_BASKET'] ? ' in-cart' : '' ?>">
                    <?if($arElement['LOCAL_MANUFACT']):?>
                        <div class="plashka own-production">
                            <div class="text">собственное<br>производство</div>
                        </div>
                    <?endif;?>
                    <?
                    $pic = '';
                    if (strlen($arElement["PICTURE"]["src"]) > 0) {
                        $pic = ' style="background: url(' . str_replace(' ', '%20', $arElement["PICTURE"]["src"]) . ') 50% 50% no-repeat;"';
                    }
                    ?>
                    <div class="search-baloon__img"<?= $pic ?>></div>
                    <div class="search-baloon-info">
                        <p class="search-baloon-info__top"><a
                                    href="<?= $section['SECTION_PAGE_URL'] ?>"><? echo $section["NAME"] ?>  </a></p>
                        <p class="search-baloon-info__description">
                            <a href="<?= $arItem['URL'] ?>">
                                <?= str_replace(['<b>', '</b>'], ['<span class="search-baloon-info__name">', '</span>'], $arItem["NAME"]) ?>
                            </a>
                        </p>
                        <div class="goods__amount" style="margin-top: 10px"><?= $title ?></div>
                    </div>
                    <div class="search-baloon-price">
                        <p class="search-baloon-info__price">Цена:<br />
                            <span class="bold"><?= $arElement['PRICES']['BASE']['VALUE'] ?></span> руб./<?= $measureTitle; ?></p>
                        <? if ($arElement['CART_PRICE']): ?>
                            <p>
                                <svg class="icon-card">
                                    <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/svg/all.svg#icon-card"></use>
                                </svg>
                                Скидка по карте Снегири<br />
                                <span class="search-baloon-info__price"><?= $arElement['PRICES']['DISCOUNT']['VALUE'] ?> руб./<?= $measureTitle; ?></span></p>
                        <? elseif ($arElement['PRICE_CARD']): ?>
                            <p>
                                <svg class="icon-card">
                                    <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/svg/all.svg#icon-card"></use>
                                </svg>
                                Цена по карте Снегири<br />
                                <span class="search-baloon-info__price"><?= $arElement['PRICES']['CARD']['VALUE'] ?> руб./<?= $measureTitle; ?></span></p>
                        <? endif; ?>
                    </div>
                    <div class="search-baloon-buttons">
                        <span class="search-baloon-buttons__text">В корзину</span>
                        <span class="search-baloon-buttons__btn mod-minus js-minus"
                              data-id="<?= $arElement['ID'] ?>">-</span>
                        <input class="search-baloon-buttons__counter js-input quantity_<?= $arElement['ID'] ?>" type="tel" value="<?= $measure ?>"
                               id="quantity_<?= $arElement['ID'] ?>"
                               data-ratio="<?= $arResult['MEASURE'][$arElement['ID']]['RATIO'] ?>"
                               data-unit="<?= $unit ?>">
                        <span class="search-baloon-buttons__unit"><?= $measureTitle; ?></span>
                        <span class="search-baloon-buttons__btn mod-plus js-plus <?= !$arElement['IN_BASKET'] ? 'add-to-cart' : '' ?>"
                              data-id="<?= $arElement['ID'] ?>">+</span>
                    </div>
                </div>


            <? endforeach; ?>

        <? endforeach; ?>
        <button class="search-baloon__button" onclick="window.location='/catalog/?q=<?=$_REQUEST['q']?>'">Показать
            все
        </button>
    </div>

    <? /* <div class="title-search-fader"></div> */ ?>
<? endif; ?>
