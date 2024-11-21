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
    <header>
		<div class="header-top">
			<div class="header-title">
				<a class="header-title-link" href="./view-posts/view-posts.php" target="content">
					<h1>Community Online Social Network (COSN)</h1>
				</a>
			</div>
		</div>
		
		  <!-- Header Menu -->
		<div class="header-tabs">
			<div class="tab">
				<a class="active" href="./members/members.php" target="content">Members</a>
			</div>
			<div class="tab">
				<a class="" href="./groups/groups.php" target="content">Groups</a>
			</div>
			<div class="tab">
				<a class="" href="./friends/friends.php" target="content">Friends</a>
			</div>
			<div class="tab">
				<a class="" href="./publish-posts/publish-posts.php" target="content">Publish Posts</a>
			</div>
			<div class="tab">
				<a class="" href="./view-posts/view-posts.php" target="content">View Posts</a>
			</div>
			<div class="tab">
				<a class="" href="./chat/chat.php" target="content">Chat</a>
			</div>
			<div class="tab">
				<a class="" href="./events/events.php" target="content">Events</a>
			</div>
			<div class="tab">
				<a class="" href="./gift-exchange/gift-exchange.php" target="content">Gift Exchange</a>
			</div>
		</div>
    </header>

    <!-- Main Layout -->
    <div class="content-area">
        <!-- Content -->
        <main class="content">
            <iframe name="content" id="iframe" src="./view-posts/view-posts.php"></iframe>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>This is a footer</p>
        <p><b><a class="footer-nav-link" href="./contact-us.php" target="content">Contact Us</a></b></p>
    </footer>

	<script>
		// Some JavaScript to handle color change on tab clicking
		document.querySelectorAll('.tab a').forEach((tab) => {
			tab.addEventListener('click', (e) => {
				document.querySelectorAll('.tab a').forEach((t) => t.classList.remove('active'));
				e.target.classList.add('active');
			});
		});
	</script>
</body>
</html>
