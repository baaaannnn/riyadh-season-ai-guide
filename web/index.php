<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Riyadh Season Guide</title>
<link rel="stylesheet" href="style_index2.css">
<link rel="stylesheet" href="header_footer.css">

</head>

<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">Riyadh Season Guide</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="login.php">Admin Login</a>
    </nav>
</header>

<div class="page-container">

    <!-- HERO -->
    <section class="hero-section">
        <div class="hero-left">
            <h1>
                <span class="green">Riyadh</span><br>
                <span class="blue">season</span><br>
                <span class="white">guide</span>
            </h1>

            <p class="hero-desc">
                Explore Riyadh Season events through our platform, and discover real visitor ratings and experiences to help you choose the best destinations and activities.
            </p>

            <!-- SEARCH + CATEGORY FILTER -->
            <form class="search-area" method="GET" action="index.php">

                <!-- Search -->
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search for events..."
                        value="<?php echo escape_html(is_string($_GET['search'] ?? null) ? $_GET['search'] : ''); ?>">
                    <button type="submit">Search →</button>
                </div>

                <!-- Category Dropdown -->
                <select name="category" class="category-filter" onchange="this.form.submit()">
                    <option value="">All Activity</option>
                    <option value="1" <?php if(($_GET['category'] ?? '') == '1') echo 'selected'; ?>>
                        Entertainment
                    </option>
                    <option value="2" <?php if(($_GET['category'] ?? '') == '2') echo 'selected'; ?>>
                        Nature
                    </option>
                </select>

            </form>
        </div>

        <div class="hero-right">
            <img src="images/logo.png" class="hero-logo">
        </div>
    </section>

    <!-- ITEMS -->
    <section class="items-container">

<?php
$conditions = [];

// SEARCH
if (!empty($_GET['search']) && is_string($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $conditions[] = "name LIKE '%$search%'";
}

// CATEGORY FILTER
if (!empty($_GET['category'])) {
    $cat = (int) $_GET['category'];
    $conditions[] = "category_id = $cat";
}

// BUILD QUERY
$query = "SELECT * FROM items";
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$result = mysqli_query($conn, $query);

// REVIEW COUNTS
$count_q = mysqli_query($conn, "SELECT item_id, COUNT(*) AS c FROM reviews GROUP BY item_id");
$counts = [];
while ($c = mysqli_fetch_assoc($count_q)) {
    $counts[$c['item_id']] = $c['c'];
}

// DISPLAY ITEMS
while ($row = mysqli_fetch_assoc($result)) {

    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= round($row['rating'])) ? "⭐" : "☆";
    }

    echo '
    <div class="item-card">
        <img src="images/'.escape_html($row['image']).'" class="item-img">
        <h3>'.escape_html($row['name']).'</h3>

        <div class="card-rating">
            '.$stars.'
            <span class="rating-num">('.($counts[$row["id"]] ?? 0).')</span>
        </div>

        <a href="item.php?id='.$row['id'].'" class="details-btn">Learn more</a>
    </div>';
}
?>

    </section>
</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
