Payssion PHP library
=====================================
This library contains payssion php client with composer installation support.
Forked and updated from originally library https://github.com/payssion/payssion-php


[![Latest Stable Version](https://poser.pugx.org/jimmlog/payssion/v/stable)](https://packagist.org/packages/jimmlog/payssion) 
[![Total Downloads](https://poser.pugx.org/jimmlog/payssion/downloads)](https://packagist.org/packages/jimmlog/payssion) 
[![Latest Unstable Version](https://poser.pugx.org/jimmlog/payssion/v/unstable)](https://packagist.org/packages/jimmlog/payssion) 
[![License](https://poser.pugx.org/jimmlog/payssion/license)](https://packagist.org/packages/jimmlog/payssion)


Integration
------------

How to integrate your website with payssion see https://payssion.com/en/docs/#integration


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist jimmlog/payssion
```

or add

```
"jimmlog/payssion": "@stable"
```

to the require section of your composer.json.

How To Use
----------

Example of use:

```
$payssion = new PayssionClient('your api key', 'your secretkey');
//please uncomment the following if you use sandbox api_key
//$payssion = new PayssionClient('your api key', 'your secretkey', false);

$response = null;
try {
	$response = $payssion->create(array(
			'amount' => 1,
			'currency' => 'USD',
			'pm_id' => 'alipay_cn',
			'order_id' => 'your order id',      //your order id
			'return_url' => 'your return url'   //optional, the return url after payments (for both of paid and non-paid)
	));
} catch (Exception $e) {
	//handle exception
	echo "Exception: " . $e->getMessage();
}

if ($payssion->isSuccess()) {
	//handle success
} else {
	//handle failed
}
```

License
-------
The MIT License (MIT). See [LICENSE](LICENSE) file.
