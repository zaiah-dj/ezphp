<html>
<head>
	<link rel="stylesheet" href="std/error.css">
</head>

<body class="_<?php echo ( $status < 500 ) ? 400 : 500; ?>">
	<div class="container">
		<h2><?php echo $status ?></h2>
		<h3><?php echo $status_line ?></h3>
		<div class="container-inner">
			<h4>Message</h4>
			<p>
				<?php echo $msg ?>
			</p>
		</div>
		<div class="container-inner">
			<h4>Stacktrace</h4>
			<p>
				<?php ; ?>
			</p>
		</div>
		<div class="container-inner">
			<h4>Code</h4>
			<pre>
				<?php ; ?>
			</pre>
		</div>
		<div class="container-footer">
			<a href="/">Go Home</a>
		</div>
	</div>
</body>
</html>
