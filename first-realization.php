<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Discount;
use Bitrix\Sale\Fuser;


class BasketOrder extends CBitrixComponent
{
	private $basket;
	private $productsPrices;

	private function getProducts(): array
	{
		$this->basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);

		if ($this->basket->isEmpty()) {
			return [];
		}

		$this->productsPrices = $this->applyDiscount();


		return $this->getProductsInBasket();
	}

	private function getProductsInBasket(): array
	{
		$products = [];
		foreach ($this->basket->getBasketItems() as $basketItem) {
			$products[] = $this->getProductInBasketItem($basketItem);
		}

		return $products;
	}

	private function getProductInBasketItem(BasketItem $basketItem): array
	{
		$priceArray = $this->productsPrices[$basketItem->getId()];

		return [
			'NAME' => $basketItem->getField('NAME'),
			'BASKET_ITEM_ID' => $basketItem->getId(),
			'PRICE' => $priceArray,
			'QUANTITY_IN_BASKET' => $basketItem->getQuantity()
		];
	}

	private function applyDiscount(): array
	{
		$context = new Discount\Context\Fuser($this->basket->getFUserId());
		$discounts = Discount::buildFromBasket($this->basket, $context);
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
