<?php
	// Sample data
	$tabs = [
		"tab1" => "Tab 1",
		"tab2" => "Tab 2",
		"tab3" => "Tab 3"
	];
	$contents = [
		"tab1" => "Content for Tab 1",
		"tab2" => "Content for Tab 2",
		"tab3" => "Content for Tab 3"
	];
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>COSN Project</title>
	<link href="./styles.css" rel="stylesheet"/>
</head>
<body>

    <!-- Header -->
    <a class="header-nav-link" href="./index.php" target="content">
        <header class="bg-primary py-3">
            <div class="header-title">
                <h1>Pawsome Pals Adoption Center</h1>
            </div>
            <div class="header-date-time"></div>
			
			<!-- Header Menu -->
			<ul class="">
				<li class="">
					<a class="active" href="content/home.html" target="content">Members</a>
				</li>
				<li class="">
					<a class="" href="content/find.html" target="content">Find a dog/cat</a>
				</li>
				<li class="">
					<a class="" href="content/dog-care.html" target="content">Dog Care</a>
				</li>
				<li class="">
					<a class="" href="content/cat-care.html" target="content">Cat Care</a>
				</li>
				<li class="">
					<a class="" href="content/create-account.html" target="content">Create an account</a>
				</li>
				<li class="">
					<a class="" href="#" data-url="check-session" target="content">Have a pet
						to give away</a>
				</li>
				<li class="">
					<a class="" href="#" data-url="logout" target="content">Log Out</a>
				</li>
				<li class="">
					<a class="nav-link" href="content/contact.html" target="content">Contact Us</a>
				</li>
			</ul>
        </header>
    </a>


    <!-- Main Layout -->
    <div class="content-area">
        <!-- Content -->
        <main class="content">
            <iframe name="content" id="iframe"></iframe>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-secondary text-white text-center py-3">
        <p>This is a footer</p>
        <p><b><a class="footer-nav-link" href="content/disclaimer.html" target="content">Privacy/Disclaimer Statement</a></b></p>
    </footer>
</body>
</html>
