<?php

use Bitrix\Main\Mail\Event;

class Helpers
{	
	public static function OnBeforeIBlockElementUpdateHandler(&$arFields)
	{
		$iblockID = CIBlockTools::GetIBlockId('catalog');

		if ($arFields['IBLOCK_ID'] == $iblockID){

			$ID = $arFields['ID'];

			$arElem = CIBlockElement::GetList(
				[],
				[
					'IBLOCK_ID' => $iblockID,
					'ID' => $ID
				],
				false,
				[ 'nTopCount' => 1 ],
				[ 'CREATED_DATE' ]
			)->fetch();

			
			if (!empty($arElem) && $arElem['CREATED_DATE']){

				$date_create = \DateTime::createFromFormat('Y.m.d', $arElem['CREATED_DATE']);
				$now = new DateTime();

				$interval = $date_create->diff($now);

				if ($interval->days < 7){
					global $APPLICATION;
					$APPLICATION->ThrowException('Товар '.$arFields["NAME"].' был создан менее одной недели назад и не может быть изменен.'); 
					return false;
				}
			}

		}			
			
	}

	public static function OnBeforeIBlockElementDeleteHandler($ID){

		$arElem = CIBlockElement::GetList(
			[],
			[
				'IBLOCK_ID' => CIBlockTools::GetIBlockId('catalog'),
				'ID' => $ID
			],
			false,
			[ 'nTopCount' => 1 ],
			[ 'NAME', 'SHOW_COUNTER' ]
		)->fetch();

		if( $arElem['SHOW_COUNTER'] > 10000){

			global $APPLICATION;
			global $USER;

			$C_fields = [
				"ID" => $USER->GetID(),
				"LOGIN" => $USER->GetLogin(),
				"NAME" => $arElem["NAME"],
				"SHOW_COUNTER" => $arElem['SHOW_COUNTER']
			];
	
			Event::send(array(
				"EVENT_NAME" => "MODER_TRY_DEL_ELEMENT",
				"LID" => "s1",
				"C_FIELDS" => $C_fields,
			));
			
			$APPLICATION->throwException("Нельзя удалить данный товар, так как он очень популярный на сайте");
			return false;
		}
	}
		
}