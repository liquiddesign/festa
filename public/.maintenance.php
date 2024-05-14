<?php

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 300'); // 5 minutes in seconds

?>
<!DOCTYPE html>
<meta charset="utf-8">
<meta name=robots content=noindex>
<meta name=generator content='Nette Framework'>

<style>
	body { color: #333; background: white; width: 800px; margin: 100px auto }
	h1 { font: bold 47px/1.5 sans-serif; margin: .6em 0 }
	p { font: 21px/1.5 Georgia,serif; margin: 1.5em 0 }
</style>

<title>Site is temporarily down for maintenance / Omlouváme se stránka je dočasně nedostupná</title>

<h1>We're Sorry / Omlouváme se</h1>

<p>The site is temporarily down for maintenance. Please try again in a few minutes.</p>
<p>Omlouváme se stránka je dočasně nedostupná. Prosím zkluste to za chvíli znovu.</p>

<?php

exit;
