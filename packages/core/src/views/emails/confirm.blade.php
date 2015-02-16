<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Welcome, {{ $user->username }}</h2>

		<div>
			To confirm your email, click here: {{ $url }}
		</div>
	</body>
</html>
