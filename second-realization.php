<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Discount;
use Bitrix\Sale\Fuser;


class BasketOrder extends CBitrixComponent
{
	private function getProducts(): array
	{
		$basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);

		if ($basket->isEmpty()) {
			return [];
		}

		$productsPrices = $this->applyDiscount($basket);

		$products = [];
		foreach ($basket->getBasketItems() as $basketItem) {
			$products[] = $this->getProductInBasketItem($basketItem, $productsPrices);
		}

		return $products;
	}

	private function getProductInBasketItem(BasketItem $basketItem, array $productsPrices): array
	{
		$priceArray = $productsPrices[$basketItem->getId()];

		return [
			'NAME' => $basketItem->getField('NAME'),
			'BASKET_ITEM_ID' => $basketItem->getId(),
			'PRICE' => $priceArray,
			'QUANTITY_IN_BASKET' => $basketItem->getQuantity()
		];
	}

	private function applyDiscount(Basket $basket): array
	{
		$context = new Discount\Context\Fuser($basket->getFUserId());
		$discounts = Discount::buildFromBasket($basket, $context);
		$discounts->calculate();
		$result = $discounts->getApplyResult(true);

		return $result['PRICES']['BASKET'];
	}

	public function executeComponent()
	{
		Loader::includeModule('sale');

		$this->arResult = $this->getProducts();

		$this->includeComponentTemplate();
	}
}
