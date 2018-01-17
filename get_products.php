<?php
//header('Content-Type: text/html; charset=windows-1251');
include 'update_curr.php';

function debug($arr){
	echo '<pre>';
	var_dump($arr);
	echo '</pre>';
}

//get availability of products in the stock
$stock_arr = array();
$offset = 0;
do {
	$url = "https://online.moysklad.ru/api/remap/1.1/report/stock/all?&limit=100&offset=".$offset;
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_USERPWD, "login : password"); 
	$result = curl_exec($ch); 
	curl_close($ch); 
	$stock = json_decode($result, true);

	foreach ($stock["rows"] as $item) {
		$stock_arr[$item['code']] = $item["stock"];
	}

	$offset += 100;
} 
while (count($stock['rows']) != 0);




$prod_data = array();
 
function translit($str) {
    $rus = array(' ', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('-', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
    return str_replace($rus, $lat, $str);
}

try {
	$db = new PDO('mysql:host=localhost;dbname=dbname', 'user', 'password');
} 
catch (PDOException $e) {
	echo $e->getMessage();
}
$table_tv = 'modx_site_tmplvar_contentvalues';
$table_content = 'modx_site_content';
$parent = 15794;
$template = 9;

$code_id = 20;
$availability_id = 51;
$price_id = 22;
$manufacturer_id = 52;
$short_description_id = 65;
$usd_tv = 58;
$rub_tv = 60;
$eur_tv = 59;
$static_info = 28;

//get currencies
$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = '.$usd_tv.' AND contentid = :contentid';
$sth = $db->prepare($sql);
$sth->execute(array(':contentid' => $static_info));
$result = $sth->fetch();
$usd = $result['value']; 

$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = '.$rub_tv.' AND contentid = :contentid';
$sth = $db->prepare($sql);
$sth->execute(array(':contentid' => $static_info));
$result = $sth->fetch();
$rub = $result['value']; 

$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = '.$eur_tv.' AND contentid = :contentid';
$sth = $db->prepare($sql);
$sth->execute(array(':contentid' => $static_info));
$result = $sth->fetch();
$eur = $result['value']; 

$i=0;

//get products
$offset = 0;
do {
    $url = "https://online.moysklad.ru/api/remap/1.1/entity/product?expand=salePrices.currency,buyPrice.currency,image&filter=https://online.moysklad.ru/api/remap/1.1/entity/product/metadata/attributes/4072c514-a34e-11e7-7a34-5acf000213b1=true&limit=100&offset=".$offset;
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_USERPWD, "login : password"); 
	$result = curl_exec($ch); 
	curl_close($ch); 
	$respon = json_decode($result, true);

	//debug($respon);

	foreach ($respon['rows'] as $product) {

		// $prod_data['id'] = $product["id"];
		// $prod_data['name'] = $product["name"];
		// $prod_data['description'] = $product["description"];
		// $prod_data['code'] = $product["code"];
		// $prod_data['externalCode'] = $product["externalCode"];
		// $prod_data['archived'] = $product["archived"];
		// $prod_data['vat'] = $product["vat"];
		// $prod_data["salePrices"] = $product["salePrices"][0]["value"];
		// $prod_data["currency"] = $product["salePrices"][0]["currency"]["code"];
		// $prod_data["article"] = $product["article"];

		//echo '$product["code"] = '.$product["code"].'<br>';

		$sql = 'SELECT * FROM '.$table_tv.' WHERE tmplvarid = 20 AND value = "'.$product["code"].'"';
		
		$sth = $db->prepare($sql);
		$sth->execute(array());
		$result = $sth->fetch();
		
		$contentid = $result['contentid'];

		$show = false;	
		$brand = '';

		foreach ($product["attributes"] as $attribute) {
			if($attribute["name"] == 'Показывать цену на сайте'){
				if($attribute["value"] === true){
					$show = true;
				}
			}
			if($attribute["name"] == 'Бренд'){
				$brand = $attribute["value"]["name"];
				$brand = iconv('UTF-8', 'windows-1251', $brand);
			}
		}	

		if($show !== true){
			$price = ' ';
		}
		else{
			$prod_data["salePrices"] = $product["salePrices"][0]["value"] / 100; //цену делим на 100, так как получаем цену в копейках
			$prod_data["currency"] = $product["salePrices"][0]["currency"]["code"];
			if($prod_data["currency"] == 643){
				$price = $prod_data["salePrices"] * $rub; 
			}
			else if($prod_data["currency"] == 840){
				$price = $prod_data["salePrices"] * $usd; 
			}
			else if($prod_data["currency"] == 978){
				$price = $prod_data["salePrices"] * $eur; 
			}
			else {
				$price = $prod_data["salePrices"]; 
			}//переводим цену в бел рубли, если она в валюте	

			//Добавляем к цене НДС
			if(($product["vat"] != 0) || ($product["vat"] !== null)){
				$vat_price = ($product["vat"]/100) * $price; 
				$full_price = $vat_price + $price;
				$price = round($full_price, 2); 	
			}

		}

		if($product["archived"] == true){//если товар архивный => наличие = под заказ
			$availability = '-';		
		}
		else{//иначе
			if (array_key_exists($product["code"], $stock_arr)) {//если товар есть в остатках => в наличие
		    	$availability = '+';
			}
			else{//иначе под заказ
				$availability = '-';	
			}	
		}

		$alias = $product["code"];
		$alias = strtolower($alias );
		$alias = str_replace('" ', '', $alias);
		$alias = str_replace('"', '', $alias);
		$alias = str_replace("'", "", $alias);
		$alias = str_replace(",", "", $alias);
		$alias = str_replace("+", "", $alias);
		$alias = str_replace('(', '_', $alias);
		$alias = str_replace(')', '_', $alias);
		$alias = str_replace("/", "-", $alias);
		$alias = translit($alias);
		$alias = str_replace("-_", "-", $alias);
		
		$product["name"] = iconv('UTF-8', 'windows-1251', $product["name"]);
		$product["code"] = iconv('UTF-8', 'windows-1251', $product["code"]);
		$product["description"] = iconv('UTF-8', 'windows-1251', $product["description"]);
		
		
		if($contentid){//Если товар на сайте есть - обновляем

			$updated = false;

			$sql = 'SELECT pagetitle, parent FROM '.$table_content.' WHERE id = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid));
			$result = $sth->fetch();
						
			$value = $result[0];
			if($product["name"]){
				if($value != $product["name"]){ $updated = true; }	
			}
			$parentId = $result[1];
			$sql = 'SELECT value FROM '.$table_tv.' WHERE contentid = :contentid AND tmplvarid = :tmplvarid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $parentId, ':tmplvarid' => 64));
			$result = $sth->fetch();
			if($result){
				$goods_title = $result[0];	
			}
			else{
				$goods_title = '';
			}
			
		
				
			$sql = 'SELECT content FROM '.$table_content.' WHERE id = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid));
			$result = $sth->fetch();
			$value = $result[0];
			if($value != $product["description"]){ $updated = true; }		

			$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = :tmplvarid AND contentid = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid, ':tmplvarid' => $code_id));
			$result = $sth->fetch();
			$value = $result[0];
			if($value != $product["code"]){ $updated = true; }

			$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = :tmplvarid AND contentid = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid, ':tmplvarid' => $availability_id));
			$result = $sth->fetch();
			$value = $result[0];
			if($value != $availability){ $updated = true; }

			$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = :tmplvarid AND contentid = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid, ':tmplvarid' => $price_id));
			$result = $sth->fetch();
			$value = $result[0];
			if($value != $price){ $updated = true; }
	
			
			$sql = "UPDATE ".$table_content." SET pagetitle = :pagetitle WHERE id = :id";
				$sth = $db->prepare($sql);				
				$sth->bindValue(":pagetitle", $product_title);
				$sth->bindValue(":id", $contentid);
				$count = $sth->execute();	


			if($updated){
				$sql = "UPDATE ".$table_content." SET `editedby` = :editedby, `editedon` = :editedon WHERE id = :id";
				$sth = $db->prepare($sql);				
				$sth->bindValue(":editedby", 1);
				$sth->bindValue(":editedon", time());
				$sth->bindValue(":id", $contentid);
				$count = $sth->execute();	
			}

			$sql = "UPDATE ".$table_tv." SET `value` = :value WHERE contentid = :contentid AND tmplvarid = :tmplvarid";
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $product["code"]);
			$sth->bindValue(":contentid", $contentid);
			$sth->bindValue(":tmplvarid", $code_id);	        
			$count = $sth->execute();

			$sql = "UPDATE ".$table_tv." SET `value` = :value WHERE contentid = :contentid AND tmplvarid = :tmplvarid";
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $availability, PDO::PARAM_STR);
			$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $availability_id, PDO::PARAM_STR);	        
			$count = $sth->execute();

			$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = :tmplvarid AND contentid = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid, ':tmplvarid' => $manufacturer_id));
			$result = $sth->fetch();
			$value = $result[0];
			if($value){
				$sql = "UPDATE ".$table_tv." SET `value` = :value WHERE contentid = :contentid AND tmplvarid = :tmplvarid";
				$sth = $db->prepare($sql);
				$sth->bindValue(":value", $brand, PDO::PARAM_STR);
				$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
				$sth->bindValue(":tmplvarid", $manufacturer_id, PDO::PARAM_STR);	        
				$count = $sth->execute();
			}
			else{
				$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
				$sth = $db->prepare($sql);
				$sth->bindValue(":value", $brand, PDO::PARAM_STR);
				$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
				$sth->bindValue(":tmplvarid", $manufacturer_id, PDO::PARAM_STR);	                                      
				$count = $sth->execute(); 
			}

			$sql = 'SELECT value FROM '.$table_tv.' WHERE tmplvarid = :tmplvarid AND contentid = :contentid';
			$sth = $db->prepare($sql);
			$sth->execute(array(':contentid' => $contentid, ':tmplvarid' => $short_description_id));
			$result = $sth->fetch();
			$value = $result[0];
			if($value){
				$sql = "UPDATE ".$table_tv." SET `value` = :value WHERE contentid = :contentid AND tmplvarid = :tmplvarid";
				$sth = $db->prepare($sql);
				$sth->bindValue(":value", $product["name"], PDO::PARAM_STR);
				$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
				$sth->bindValue(":tmplvarid", $short_description_id, PDO::PARAM_STR);	        
				$count = $sth->execute();
			}
			else{
				$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
				$sth = $db->prepare($sql);
				$sth->bindValue(":value", $product["name"], PDO::PARAM_STR);
				$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
				$sth->bindValue(":tmplvarid", $short_description_id, PDO::PARAM_STR);	                                      
				$count = $sth->execute(); 
			}

			$sql = "UPDATE ".$table_tv." SET `value` = :value WHERE contentid = :contentid AND tmplvarid = :tmplvarid";
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $price, PDO::PARAM_STR);
			$sth->bindValue(":contentid", $contentid, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $price_id, PDO::PARAM_STR);	        
			$count = $sth->execute();


		}
		else{//если товара нет - добавляем
			
			$sth = $db->prepare("INSERT INTO ".$table_content." (pagetitle, alias, template, parent, published) VALUES (:pagetitle,:alias, :template,:parent, :published)");
			$sth->execute(array(
				'pagetitle'=>$product_title, 'alias'=>translit($alias), 'template'=>$template, 'parent'=>$parent, 'published' => 1
			));
			$lastInsertId = $db->lastInsertId();

			$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $product["code"], PDO::PARAM_STR);
			$sth->bindValue(":contentid", $lastInsertId, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $code_id, PDO::PARAM_STR);	                                      
			$count = $sth->execute(); 

			$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $availability, PDO::PARAM_STR);
			$sth->bindValue(":contentid", $lastInsertId, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $availability_id, PDO::PARAM_STR);	                                      
			$count = $sth->execute(); 

			$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $brand, PDO::PARAM_STR);
			$sth->bindValue(":contentid", $lastInsertId, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $manufacturer_id, PDO::PARAM_STR);	                                      
			$count = $sth->execute(); 

			$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
			$sth = $db->prepare($sql);
			$sth->bindValue(":value", $product["name"], PDO::PARAM_STR);
			$sth->bindValue(":contentid", $lastInsertId, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $short_description_id, PDO::PARAM_STR);	                                      
			$count = $sth->execute(); 

			$sql = "INSERT INTO ".$table_tv." (contentid, tmplvarid, value) VALUES (:contentid, :tmplvarid, :value)";                            
			$sth = $db->prepare($sql);
			$sth->bindValue(":value",  $price, PDO::PARAM_STR);
			$sth->bindValue(":contentid", $lastInsertId, PDO::PARAM_STR);
			$sth->bindValue(":tmplvarid", $price_id, PDO::PARAM_STR);	                                      
			$count = $sth->execute(); 
		
		}


	}

	$offset += 100;
} 

while (count($respon['rows']) != 0);
