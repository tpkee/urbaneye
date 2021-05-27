<?php
    session_start();
    if (empty($_GET["q"])){
        header("location:../index.html");
    }
    require("../api/dbConn.php");
    $db = db_connection();
    $type = $_GET["t"];
    $searchQuery = $_GET["q"];
    $isInserted = false;
    $isFavourite = false;
    $results = array();
    if ($type == "city"){
        $sql = "
            SELECT cityID, cityImage, cityName, countryName, AVG(reviewTaxes) as taxes, AVG(reviewEnvironment) as environment, AVG(reviewCOL) AS col, AVG(reviewSecurity) AS security, (AVG(reviewTaxes)+AVG(reviewEnvironment)+AVG(reviewCOL)+AVG(reviewSecurity))/4 as overallScore
            FROM Cities
            JOIN Countries ON cityCountryID = countryID
            LEFT JOIN Reviews ON cityID = reviewCityID
            WHERE cityID = ?
        ";
        $res = $db->prepare($sql);
        $res->execute(array($searchQuery));
        $r = $res->fetch(PDO::FETCH_ASSOC);
        $results[] = [$r["cityName"], $r["countryName"], [isNull($r["taxes"]), isNull($r["environment"]), isNull($r["security"]), isNull($r["col"]), isNull($r["overallScore"])], $r["cityImage"], $r["cityID"]];
        $res = $db->prepare($sql);
        $sql = "
            SELECT *
            FROM Favourites
            WHERE favouriteCityID = ? AND favouriteUserID = (SELECT userID FROM Users WHERE userUsername = ?)
        ";
        $res = $db->prepare($sql);
        $res->execute(array($searchQuery, $_SESSION["userSession"]));
        if ($res->rowCount()){
            $isFavourite = true;
        }
    } else {
        $sql = "
            SELECT cityID, cityImage, cityName, countryName, AVG(reviewTaxes) as taxes, AVG(reviewEnvironment) as environment, AVG(reviewCOL) AS col, AVG(reviewSecurity) AS security, (AVG(reviewTaxes)+AVG(reviewEnvironment)+AVG(reviewCOL)+AVG(reviewSecurity))/4 as overallScore
            FROM Countries
            JOIN Cities ON countryID = cityCountryID
            LEFT JOIN Reviews ON reviewCityID = cityID
            WHERE countryID = ?
            GROUP BY cityID
            ORDER BY overallScore DESC
        ";
        $res = $db->prepare($sql);
        $res->execute(array($searchQuery));
        $res = $res->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res as $r){
            $results[] = [$r["cityName"], $r["countryName"], [isNull($r["taxes"]), isNull($r["environment"]), isNull($r["security"]), isNull($r["col"]), isNull($r["overallScore"])], $r["cityImage"], $r["cityID"]];
        }
    }
    $db = null;
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if ($type == "city"){ echo $results[0][0]; } else { echo $results[0][1]; } ?> - Urbaneye</title>
    <link rel="stylesheet" href="../static/css/output.css">
    <link rel="stylesheet" href="../static/css/custom.css">
    <script src="../static/js/main.js"></script>
    <script src="../static/js/jquery-3.6.0-min.js"></script>
    <script>
        $(document, '.modal-overlay').click(function() {
            if ($('#profile-drop').is(':visible')) {
                $('#profile-drop').hide();
            }
            if ($('#login').is(':visible')) {
                $('#login').hide();
                $('#username').val('');
                $('#password').val('');
                $("#incorrectDataLogin").hide("fast");
            } else if ($('#signup').is(':visible')){
                $('#signup').hide();
                $("#takenUsername").hide("fast");
                $("#takenMail").hide("fast");
                $('#email').val('');
                $('#password-su').val('');
                $('#username-su').val('');
            } else if($('#review-inserter').is(':visible')){
                $("#")
            }
        });
        $(document).ready(function() {
            let generated = 0, lastGeneration = new Date().getTime();
            const data = <?php echo json_encode($results); ?>, toGenerate = <?php if ($type == "city"){echo 1;}else{ echo 28;}?>;
            $('#live-search').val('');
            isLogged();
            login();
            signup();
            liveSearch(5);
            cityWildcardGen(generated, data.slice(generated, toGenerate));
            generated += toGenerate;
            if (generated < data.length) {
                $(window).on("scroll", () => {
                    console.log(generated);
                    let t = new Date().getTime();
                    let scrollHeight = $(document).height();
                    let scrollPos = Math.floor($(window).height() + $(window).scrollTop());
                    if (scrollHeight - 300 < scrollPos) {
                        if (t > lastGeneration + 650) {
                            lastGeneration = t;
                            $('#cards-loader').hide("fast");
                            cityWildcardGen(generated, toGenerate, data.slice(generated, generated+toGenerate));
                            generated += toGenerate;
                        } else {
                            $('#cards-loader').show("fast");
                        }
                    }
                });
            }
            $('#profile-drop, #user-profile, #login-nav, #login-form, #signup-form, #review-form').click(function (evt) {
                evt.stopPropagation();
            });
        });
    </script>
</head>
<body>

<div class="w-full text-gray-200">
    <div class="flex flex-col max-w-screen-2xl px-4 mx-auto lg:items-center lg:justify-between lg:flex-row md:px-6 lg:px-8">
        <div class="p-4 flex flex-row items-center justify-between">
            <span class="sr-only">Urbaneye logo</span>
            <!-- mobile -->
            <svg class="block lg:hidden h-8 w-auto" width="27" height="31" viewBox="0 0 27 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.936 30.864C11.92 30.864 10.112 30.592 8.512 30.048C6.912 29.504 5.552 28.736 4.432 27.744C3.312 26.72 2.448 25.504 1.84 24.096C1.264 22.656 0.976 21.072 0.976 19.344V0.815998C1.36 0.751999 1.92 0.671999 2.656 0.575998C3.392 0.447998 4.128 0.383998 4.864 0.383998C6.464 0.383998 7.616 0.655998 8.32 1.2C9.024 1.744 9.376 2.8 9.376 4.368V19.2C9.376 20.736 9.792 21.936 10.624 22.8C11.488 23.664 12.592 24.096 13.936 24.096C15.28 24.096 16.368 23.664 17.2 22.8C18.064 21.936 18.496 20.736 18.496 19.2V0.815998C18.88 0.751999 19.44 0.671999 20.176 0.575998C20.912 0.447998 21.648 0.383998 22.384 0.383998C23.984 0.383998 25.136 0.655998 25.84 1.2C26.544 1.744 26.896 2.8 26.896 4.368V19.344C26.896 21.072 26.592 22.656 25.984 24.096C25.408 25.504 24.56 26.72 23.44 27.744C22.32 28.736 20.96 29.504 19.36 30.048C17.76 30.592 15.952 30.864 13.936 30.864Z" fill="#F3EFF5" />
            </svg>
            <!-- desktop -->
            <svg class="hidden lg:block" width="300" height="65" viewBox="0 0 344 74" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M40.625 3C41.5625 3 42.375 3.34375 43.0625 4.03125C43.8125 4.71875 44.1875 5.5625 44.1875 6.5625V36.9375C44.1875 43.625 41.7812 48.875 36.9688 52.6875C32.8438 56.0625 27.875 57.75 22.0625 57.75C16.1875 57.75 11.2188 56.0625 7.15625 52.6875C2.40625 48.8125 0.03125 43.5625 0.03125 36.9375V6.5625C0.03125 5.625 0.375 4.8125 1.0625 4.125C1.75 3.375 2.5625 3 3.5 3C4.4375 3 5.25 3.34375 5.9375 4.03125C6.6875 4.71875 7.0625 5.5625 7.0625 6.5625V37.125C7.0625 41.6875 8.625 45.25 11.75 47.8125C14.625 50.1875 18.0625 51.375 22.0625 51.375C26.125 51.375 29.5938 50.1875 32.4688 47.8125C35.5938 45.1875 37.1562 41.625 37.1562 37.125V6.5625C37.1562 5.625 37.5 4.8125 38.1875 4.125C38.875 3.375 39.6875 3 40.625 3ZM64.1562 24.2812C67.7812 19.4688 71.8125 17.0625 76.25 17.0625H76.625C77.5625 17.0625 78.3438 17.4062 78.9688 18.0938C79.6562 18.7188 80 19.5312 80 20.5312C80 21.5312 79.6562 22.3438 78.9688 22.9688C78.2812 23.5312 77.4375 23.8125 76.4375 23.8125H76.0625C73.5 23.8125 71.2188 24.4375 69.2188 25.6875C67.2188 26.9375 65.5312 28.5625 64.1562 30.5625V53.9062C64.1562 54.8438 63.8125 55.6562 63.125 56.3438C62.4375 57.0312 61.625 57.375 60.6875 57.375C59.75 57.375 58.9375 57.0312 58.25 56.3438C57.5625 55.6562 57.2188 54.8438 57.2188 53.9062V21C57.2188 20.0625 57.5312 19.25 58.1562 18.5625C58.8438 17.8125 59.6875 17.4375 60.6875 17.4375C61.625 17.4375 62.4375 17.7812 63.125 18.4688C63.8125 19.1562 64.1562 20 64.1562 21V24.2812ZM93.7812 22.875C97.5938 19 101.969 17.0625 106.906 17.0625C111.906 17.0625 116.156 18.8125 119.656 22.3125C123.531 26.1875 125.5 31.2188 125.562 37.4062C125.562 43.6562 123.594 48.7188 119.656 52.5938C116.031 56.0938 111.75 57.8125 106.812 57.75C101.938 57.75 97.5938 55.8125 93.7812 51.9375V53.9062C93.7812 54.8438 93.4375 55.6562 92.75 56.3438C92.125 57.0312 91.3125 57.375 90.3125 57.375C89.3125 57.375 88.4688 57.0312 87.7812 56.3438C87.1562 55.6562 86.8438 54.8438 86.8438 53.9062V4.03125C86.8438 3.03125 87.1562 2.21875 87.7812 1.59375C88.4688 0.90625 89.3125 0.5625 90.3125 0.5625C91.3125 0.5625 92.125 0.90625 92.75 1.59375C93.4375 2.21875 93.7812 3.03125 93.7812 4.03125V22.875ZM93.7812 45.4688C97.2812 49.3438 101.344 51.25 105.969 51.1875C111.844 51.1875 115.75 48.5938 117.688 43.4062C118.312 41.6562 118.594 39.6562 118.531 37.4062C118.531 33.0312 117.375 29.5938 115.062 27.0938C112.812 24.7812 109.781 23.625 105.969 23.625C101.344 23.625 97.2812 25.5312 93.7812 29.3438V45.4688ZM136.625 19.7812C140.938 17.9062 145.406 17 150.031 17.0625C155.219 17.0625 159.25 18.6562 162.125 21.8438C164.688 24.5938 166 28.0312 166.062 32.1562V53.9062C166.062 54.8438 165.688 55.6562 164.938 56.3438C164.25 57.0312 163.406 57.375 162.406 57.375C161.469 57.375 160.656 57.0312 159.969 56.3438C159.281 55.6562 158.938 54.8438 158.938 53.9062V51.9375C157.562 53.375 155.719 54.7188 153.406 55.9688C151.094 57.1562 148.156 57.75 144.594 57.75C141.031 57.75 138.062 56.7188 135.688 54.6562C133.25 52.5312 132.031 49.75 132.031 46.3125C132.031 42.5 133.469 39.5 136.344 37.3125C139.469 34.9375 143.906 33.7812 149.656 33.8438H158.938V33.1875C158.938 29.75 158.094 27.3125 156.406 25.875C154.781 24.4375 152.219 23.7188 148.719 23.7188C145.781 23.7188 142.375 24.3125 138.5 25.5C138.125 25.625 137.656 25.6875 137.094 25.6875C136.594 25.6875 136.062 25.4375 135.5 24.9375C134.938 24.375 134.625 23.5312 134.562 22.4062C134.562 21.2188 135.25 20.3438 136.625 19.7812ZM139.062 45.4688C139.062 49.5938 141.781 51.625 147.219 51.5625C149.594 51.5625 151.812 51 153.875 49.875C156 48.6875 157.688 47.375 158.938 45.9375V39.4688H150.688C147.062 39.4688 144.219 39.9375 142.156 40.875C140.094 41.75 139.062 43.2812 139.062 45.4688ZM197.375 23.5312C192.875 23.5312 188.656 26 184.719 30.9375V53.9062C184.719 54.8438 184.375 55.6562 183.688 56.3438C183 57.0312 182.188 57.375 181.25 57.375C180.312 57.375 179.5 57.0312 178.812 56.3438C178.125 55.6562 177.781 54.8438 177.781 53.9062V21C177.781 20 178.094 19.1562 178.719 18.4688C179.406 17.7812 180.25 17.4375 181.25 17.4375C182.188 17.4375 183 17.7812 183.688 18.4688C184.375 19.0938 184.719 19.9375 184.719 21V24.1875C186.781 22.0625 188.562 20.5312 190.062 19.5938C192.938 17.9062 195.688 17.0625 198.312 17.0625C201 17.0625 203.188 17.4688 204.875 18.2812C206.562 19.0312 208 20.125 209.188 21.5625C211.562 24.375 212.75 27.875 212.75 32.0625V53.9062C212.75 54.8438 212.406 55.6562 211.719 56.3438C211.031 57.0312 210.219 57.375 209.281 57.375C208.344 57.375 207.531 57.0312 206.844 56.3438C206.156 55.6562 205.812 54.8438 205.812 53.9062V32.8125C205.812 29.9375 205.156 27.6875 203.844 26.0625C202.531 24.375 200.375 23.5312 197.375 23.5312ZM229.156 39.9375C229.594 43.375 230.906 46.125 233.094 48.1875C235.344 50.1875 238.406 51.1875 242.281 51.1875C246.219 51.1875 249.562 50.5625 252.312 49.3125C253.188 48.875 253.969 48.6562 254.656 48.6562C255.344 48.5938 255.969 48.8438 256.531 49.4062C257.156 49.9688 257.469 50.5938 257.469 51.2812C257.469 52.6562 256.906 53.625 255.781 54.1875C254.719 54.75 253.75 55.25 252.875 55.6875C252 56.125 251.062 56.5 250.062 56.8125C247.75 57.4375 245.062 57.75 242 57.75C235.688 57.75 230.781 56 227.281 52.5C223.844 48.9375 222.125 43.9062 222.125 37.4062C222.125 31.7812 223.594 27.0938 226.531 23.3438C229.844 19.1562 234.5 17.0625 240.5 17.0625C246.25 17.0625 250.781 19 254.094 22.875C257.219 26.5 258.781 31.0312 258.781 36.4688C258.781 37.4062 258.438 38.2188 257.75 38.9062C257.125 39.5938 256.312 39.9375 255.312 39.9375H229.156ZM240.5 22.9688C235.938 22.9688 232.625 25 230.562 29.0625C229.812 30.5 229.344 32.2188 229.156 34.2188H251.844C251.656 30.5938 250.312 27.7188 247.812 25.5938C245.812 23.8438 243.375 22.9688 240.5 22.9688ZM297.688 17.4375C298.688 17.4375 299.5 17.7812 300.125 18.4688C300.812 19.1562 301.156 19.875 301.156 20.625C301.156 21.375 301.062 21.9375 300.875 22.3125L279.969 72.6562C279.344 74.2812 278.281 75.0938 276.781 75.0938C275.906 75.0938 275.125 74.75 274.438 74.0625C273.812 73.375 273.5 72.6875 273.5 72C273.5 71.375 273.562 70.8438 273.688 70.4062L278.656 57.5625L264.219 22.4062C264.031 21.9062 263.938 21.3125 263.938 20.625C263.938 19.875 264.25 19.1562 264.875 18.4688C265.562 17.7812 266.312 17.4375 267.125 17.4375C268.812 17.4375 269.969 18.1562 270.594 19.5938L282.406 48.9375L294.5 19.6875C295.125 18.125 296.188 17.375 297.688 17.4375ZM313.719 39.9375C314.156 43.375 315.469 46.125 317.656 48.1875C319.906 50.1875 322.969 51.1875 326.844 51.1875C330.781 51.1875 334.125 50.5625 336.875 49.3125C337.75 48.875 338.531 48.6562 339.219 48.6562C339.906 48.5938 340.531 48.8438 341.094 49.4062C341.719 49.9688 342.031 50.5938 342.031 51.2812C342.031 52.6562 341.469 53.625 340.344 54.1875C339.281 54.75 338.312 55.25 337.438 55.6875C336.562 56.125 335.625 56.5 334.625 56.8125C332.312 57.4375 329.625 57.75 326.562 57.75C320.25 57.75 315.344 56 311.844 52.5C308.406 48.9375 306.688 43.9062 306.688 37.4062C306.688 31.7812 308.156 27.0938 311.094 23.3438C314.406 19.1562 319.062 17.0625 325.062 17.0625C330.812 17.0625 335.344 19 338.656 22.875C341.781 26.5 343.344 31.0312 343.344 36.4688C343.344 37.4062 343 38.2188 342.312 38.9062C341.688 39.5938 340.875 39.9375 339.875 39.9375H313.719ZM325.062 22.9688C320.5 22.9688 317.188 25 315.125 29.0625C314.375 30.5 313.906 32.2188 313.719 34.2188H336.406C336.219 30.5938 334.875 27.7188 332.375 25.5938C330.375 23.8438 327.938 22.9688 325.062 22.9688Z" fill="#EEEEEE" />
            </svg>
        </div>
        <div id="searchbar" class="pt-2 relative mx-auto text-darkText w-1/2 -top-12 lg:top-0">
            <label>
                <input id="live-search" class="bg-custom-ghost w-full h-6 text-xs sm:h-10 px-7 sm:px-9 rounded-md sm:text-sm focus:outline-none" type="search" name="searchData" placeholder="Search city or country..." />
            </label>
            <span class="absolute left-1.5 sm:left-3 -top-1.5 sm:top-0 mt-5 mr-4">
          <svg class="text-gray-600 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 56.966 56.966" xml:space="preserve" width="512px" height="512px">
              <path d="M55.146,51.887L41.588,37.786c3.486-4.144,5.396-9.358,5.396-14.786c0-12.682-10.318-23-23-23s-23,10.318-23,23  s10.318,23,23,23c4.761,0,9.298-1.436,13.177-4.162l13.661,14.208c0.571,0.593,1.339,0.92,2.162,0.92  c0.779,0,1.518-0.297,2.079-0.837C56.255,54.982,56.293,53.08,55.146,51.887z M23.984,6c9.374,0,17,7.626,17,17s-7.626,17-17,17  s-17-7.626-17-17S14.61,6,23.984,6z" />
                </svg>
        </span>

            <div id="search-loader" class="absolute bg-custom-dark shadow-lg rounded-md top-100 left-0 z-40 p-4 w-full mx-auto overflow-hidden" style="display: none">
                <div class="animate-pulse flex space-x-4">
                    <div class="rounded-full bg-custom-ghost h-12 w-12"></div>
                    <div class="flex-1 space-y-4 py-1">
                        <div class="h-4 bg-custom-ghost rounded w-3/4"></div>
                        <div class="space-y-2">
                            <div class="h-4 bg-custom-ghost rounded"></div>
                            <div class="h-4 bg-custom-ghost rounded w-5/6"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="search-results" class="absolute shadow bg-custom-dark top-100 z-40 w-full left-0 rounded-md max-h-select overflow-hidden" style="display: none">
                <div class="flex flex-col w-full">
                    <div id="single-result" class="cursor-pointer w-full border-gray-400 border-b" style="display: none">
                        <div class="flex w-full items-center p-2 pl-2 relative">
                            <div class="w-6 flex flex-col items-center">
                                <div class="flex relative w-5 h-5 justify-center items-center m-1 mr-2 w-4 h-4 mt-1">
                                    <svg id="search-default" style="display: none" xmlns="http://www.w3.org/2000/svg"  width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M21 3l-6.5 18a0.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a0.55 .55 0 0 1 0 -1l18 -6.5" />
                                    </svg>
                                    <svg id="search-trending" style="display: none" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F9A620" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full items-center flex">
                                <div class="mx-2 -mt-1"><a id="result-name" class="text-lightText" href="#"></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="result-lost" class="cursor-pointer w-full" style="display: none">
                    <div class="flex w-full items-center p-2 pl-2 relative">
                        <div class="w-6 flex flex-col items-center">
                            <div class="flex relative w-5 h-5 justify-center items-center m-1 mr-2 w-4 h-4 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M8 8a3.5 3 0 0 1 3.5 -3h1a3.5 3 0 0 1 3.5 3a3 3 0 0 1 -2 3a3 4 0 0 0 -2 4" />
                                    <line x1="12" y1="19" x2="12" y2="19.01" />
                                </svg>
                            </div>
                        </div>
                        <div class="w-full items-center flex">
                            <div class="mx-2 -mt-1"><p id="search-lost" class="text-custom-warning">Not all those who wander are lost</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <nav class="flex-col flex-grow pb-4 lg:pb-0 lg:flex lg:justify-end lg:flex-row invisible lg:visible">
            <div class="invisible lg:visible lg:flex relative -top-40 md:top-2 left-2">
                <div class="buttonContainer w-28">
                    <svg viewBox="0 0 100 30" xmlns="http://www.w3.org/2000/svg">
                        <rect class="buttRect" height="30" width="100" />
                    </svg>
                    <a href="../index.html" style="font-family: 'Open Sans', sans-serif" class="relative -top-2/4 flex justify-center uppercase text-lightText text-sm not-sr-only"
                    >Home</a>
                </div>
                <!-- end nav button -->
                <div class="buttonContainer w-28">
                    <svg viewBox="0 0 100 30" xmlns="http://www.w3.org/2000/svg">
                        <rect class="buttRect" height="30" width="100" />
                    </svg>
                    <a href="#" class="relative -top-2/4 flex justify-center uppercase text-lightText text-sm not-sr-only"
                    >Compare</a>
                </div>
                <div id="login-nav" style="display: none;">
                    <div class="invisible lg:visible buttonContainer w-28">
                        <svg viewBox="0 0 100 30" xmlns="http://www.w3.org/2000/svg">
                            <rect class="buttRect" height="30" width="100"></rect>
                        </svg>
                        <a class="relative -top-2/4 flex justify-center uppercase text-lightText text-sm not-sr-only cursor-pointer" onclick="$('#login').show('fast')">Login</a>
                    </div>
                    <div class="visible lg:invisible">
                        <button class="focus:outline-none absolute right-5 -top-2/4 md:-top-24 md:right-16" onclick="$('#login').show('fast')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3EFF5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                                <path d="M20 12h-13l3 -3m0 6l-3 -3" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button id="user-profile" class="visible focus:outline-none absolute right-5 lg:relative lg:right-auto lg:top-auto md:top-7 md:right-16" onclick="userDrop()" style="display: none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3EFF5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="7" r="4" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                    </svg>
                </button>
            </div>
            <div id="profile-drop" class="absolute my-10 z-40" style="display: none">
                <div class="bg-custom-dark rounded overflow-hidden shadow-lg">
                    <div class="text-center p-6 border-b">
                        <img
                                id="drop-propic"
                                class="h-24 w-24 rounded-full mx-auto"
                                src="../assets/img/cities/default.jpg"
                                alt="profile picture"
                        />
                        <p id="drop-username" class="pt-2 text-lg font-semibold"></p>
                        <p id="drop-email" class="text-sm text-gray-300"></p>
                    </div>
                    <div class="border-b">
                        <a href="#" class="px-4 py-2 hover:bg-gray-700 flex">
                            <div>
                                <svg
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1"
                                        viewBox="0 0 24 24"
                                        class="w-5 h-5"
                                >
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="pl-3">
                                <p class="text-sm font-medium leading-none">Account settings</p>
                                <p class="text-xs text-gray-300">Security, privacy, email</p>
                            </div>
                        </a>
                        <a href="#" class="px-4 py-2 hover:bg-gray-700 flex">
                            <div>
                                <svg
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1"
                                        viewBox="0 0 24 24"
                                        class="w-5 h-5"
                                >
                                    <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="pl-3">
                                <p class="text-sm font-medium leading-none">Personal settings</p>
                                <p class="text-xs text-gray-300">Profile, notifications</p>
                            </div>
                        </a>
                        <a href="favourites.html" class="px-4 py-2 hover:bg-gray-700 flex">
                            <div>
                                <svg
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1"
                                        viewBox="0 0 24 24"
                                        class="w-5 h-5"
                                >
                                    <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <div class="pl-3">
                                    Favourites
                                <p class="text-xs text-gray-300">The cities you love</p>
                            </div>
                        </a>
                    </div>

                    <div>
                        <a href="#" class="px-4 py-2 pb-4 hover:bg-gray-700 flex" onclick="logout()">
                            <p class="text-sm font-medium leading-none">Logout</p>
                        </a>
                    </div>
                </div>
            </div>

        </nav>
    </div>
    <nav class="fixed bottom-0 left-0 z-40 w-full lg:hidden">
        <div class="bg-custom-dark h-14">
            <div class="relative flex justify-around">
                <div class="grid justify-items-center relative -top-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="relative top-4" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3EFF5" fill="#F3EFF5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="5 12 3 12 12 3 21 12 19 12" />
                        <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                        <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" stroke="#000" />
                    </svg>

                    <a
                            class="px-4 py-2 mt-2 text-md text-lightText focus:outline-none focus:shadow-outline"
                            href="./index.html"
                    >Home</a>
                </div>
                <div class="grid justify-items-center relative -top-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="relative top-4" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3EFF5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="6" height="6" rx="1" />
                        <rect x="15" y="15" width="6" height="6" rx="1" />
                        <path d="M21 11v-3a2 2 0 0 0 -2 -2h-6l3 3m0 -6l-3 3" />
                        <path d="M3 13v3a2 2 0 0 0 2 2h6l-3 -3m0 6l3 -3" />
                    </svg>
                    <a
                            class="active:bg-red-200 px-4 py-2 mt-2 text-md text-lightText focus:outline-none focus:shadow-outline"
                            href="#"
                    >Compare</a>
                </div>

                <div class="grid justify-items-center relative -top-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="relative top-4" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3EFF5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <a
                            class="active:bg-red-200 px-4 py-2 mt-2 text-md text-lightText focus:outline-none focus:shadow-outline"
                            href="#"
                    >Favourites</a>
                </div>
            </div>
        </div>
    </nav>
</div>
<div id="login" class="modal z-40 absolute w-full h-full top-0 left-0 flex items-center justify-center" style="display: none">
    <div class="modal-overlay absolute w-full h-full bg-black opacity-25 top-0 left-0 cursor-pointer z-0"></div>
    <div class="absolute flex flex-col bg-custom-dark shadow-lg px-4 sm:px-6 md:px-8 lg:px-10 py-8 rounded-md w-full max-w-md">
        <div class="font-medium self-center text-xl sm:text-2xl uppercase">Login To Your Account</div>
        <div class="mt-10">
            <form id="login-form" method="post">
                <div class="flex flex-col mb-6">
                    <label for="username" class="mb-1 text-xs sm:text-sm tracking-wide">Username</label>
                    <div class="relative">
                        <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                            <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>

                        <input id="username" required="required" type="text" name="username" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                    </div>
                </div>
                <div class="flex flex-col mb-6">
                    <label for="password" class="mb-1 text-xs sm:text-sm tracking-wide">Password</label>
                    <div class="relative">
                        <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
              <span>
                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                  <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
                        </div>

                        <input id="password" required="required" type="password" name="password" class="text-sm sm:text-base pl-10 pr-4 rounded-lg   bg-custom-eerie w-full py-2 focus:outline-none"/>
                    </div>
                </div>
                <div class="flex items-center mb-6 -mt-4">
                    <div id="incorrectDataLogin" class="text-custom-warning text-sm" style="display: none">Incorrect login data!</div>

                    <div class="flex ml-auto">
                        <a href="#" class="inline-flex text-xs sm:text-sm hover:text-custom-evil-cayola">Forgot Your Password?</a>
                    </div>
                </div>
                <div class="flex w-full">
                    <button type="submit" class="flex items-center justify-center focus:outline-none text-custom-ghost text-sm sm:text-base bg-custom-cayola hover:bg-custom-evil-cayola rounded py-2 w-full transition duration-150 ease-in" onclick="login()">
                        <span class="mr-2 uppercase">Log In</span>
                        <span>
                                  <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                  </svg>
                                </span>
                    </button>
                </div>
            </form>
        </div>
        <div class="flex justify-center items-center mt-6">
            <a onclick="$('#signup').show()" class="inline-flex items-center font-bold text-custom-ghost hover:text-custom-evil-cayola text-xs text-center">
        <span>
          <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </span>
                <span class="ml-2 cursor-pointer">You don't have an account?</span>
            </a>
        </div>
    </div>
</div>

<div id="signup" class="modal z-50 absolute w-full h-full top-0 left-0 flex items-center justify-center" style="display: none;">
    <div class="modal-overlay absolute w-full h-full bg-black opacity-25 top-0 left-0 cursor-pointer"></div>
    <div class="absolute flex flex-col bg-custom-dark shadow-lg px-4 sm:px-6 md:px-8 lg:px-10 py-8 rounded-md w-full max-w-md">
        <div class="font-medium self-center text-xl sm:text-2xl uppercase">Join us!</div>
        <div class="mt-10">
            <form id="signup-form" method="post">
                <div class="flex flex-col mb-6">
                    <label for="username-su" class="mb-1 text-xs sm:text-sm tracking-wide">Username</label>
                    <div class="relative">
                        <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                            <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>

                        <input id="username-su" required="required" type="text" name="username" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                    </div>
                    <div id="takenUsername" class="text-custom-warning text-sm" style="display: none">This username is already taken</div>
                </div>
                <div class="flex flex-col mb-6">
                    <label for="password-su" class="mb-1 text-xs sm:text-sm tracking-wide">Password</label>
                    <div class="relative">
                        <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
            <span>
              <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </span>
                        </div>

                        <input id="password-su" required="required" type="password" name="password" class="text-sm sm:text-base pl-10 pr-4 rounded-lg   bg-custom-eerie w-full py-2 focus:outline-none"/>
                    </div>
                </div>
                <div class="flex flex-col mb-6">
                    <label for="email" class="mb-1 text-xs sm:text-sm tracking-wide">Email</label>
                    <div class="relative">
                        <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                            <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>

                        <input id="email" required="required" type="email" name="email" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                    </div>
                    <div id="takenMail" class="text-custom-warning text-sm" style="display: none">This mail is already taken</div>
                </div>
                <div class="flex items-center mb-6 -mt-4">
                    <div class="flex ml-auto">
                    </div>
                </div>
                <div class="flex w-full">
                    <button type="submit" class="flex items-center justify-center focus:outline-none text-custom-ghost text-sm sm:text-base bg-custom-cayola hover:bg-custom-evil-cayola rounded py-2 w-full transition duration-150 ease-in" onclick="signup()">
                        <span class="mr-2 uppercase">Sign Up</span>
                        <span>
          <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="grid justify-center">
    <div class="container sm:m-20">
        <h1 class="not-sr-only">
            <?php if ($type == "city"){ echo $results[0][0], ' in ', $results[0][1]; } else { echo $results[0][1]; } ?>
        </h1>
    </div>
<div class="flex justify-center">
    <div id="cities-section" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-7 gap-5">
        <div id="single-city" class="relative object-cover select-none shadow-wildcard rounded-xl cursor-pointer w-44 h-40 md:w-60 md:h-64" style="display:none">
            <a id="city-page" href="">
            <img id="city-bg" class="z-0 absolute h-full w-auto rounded-xl object-cover" src="" alt="city image">
            <div id="city-shadow-bg" class="z-1 absolute h-full w-full object-fill bg-custom-eerie opacity-40"></div>
            <!-- wildcard is hovered -->
            <div id="grade-progress" class="z-10 relative text-lightText flex flex-col h-full justify-around" style="display:none;" onmouseover="cityWildcardHovered(this.id, true)" onmouseleave="cityWildcardHovered(this.id, false)">
                <div class="m-5 flex flex-col w-auto h-auto justify-items-stretch">
                    <h5 class="not-sr-only">Taxes</h5>
                    <div class="h-3 relative max-w-xl rounded-full overflow-hidden progressAnimated">
                        <div class="w-full h-full bg-gray-500 opacity-20 absolute"></div>
                        <div id="overall-taxes" class="relative h-full bg-green-500">
                            <span class="sr-only">{{ overallTaxes + '%' }}</span>
                        </div>
                    </div>
                    <h5 class="not-sr-only">Cost of Life</h5>
                    <div class="h-3 relative max-w-xl rounded-full overflow-hidden progressAnimated">
                        <div class="w-full h-full bg-gray-300 opacity-20 absolute"></div>
                        <div id="overall-col" class="relative h-full bg-green-500">
                            <span class="sr-only">{{ overallCoL + '%' }}</span>
                        </div>
                    </div>
                    <h5 class="not-sr-only">Environment</h5>
                    <div class="h-3 relative max-w-xl rounded-full overflow-hidden progressAnimated">
                        <div class="w-full h-full bg-gray-500 opacity-25 absolute"></div>
                        <div id="overall-environment" class="relative h-full bg-green-500">
                            <span class="sr-only">{{ overallEnvironment + '%' }}%</span>
                        </div>
                    </div>
                    <h5 class="not-sr-only">Security</h5>
                    <div class="h-3 relative max-w-xl rounded-full overflow-hidden progressAnimated">
                        <div class="w-full h-full bg-gray-500 opacity-25 absolute"></div>
                        <div id="overall-security" class="relative h-full bg-green-500">
                            <span class="sr-only">{{ overallSecurity + '%' }}%</span>
                        </div>
                    </div>
                    <div class="relative left-1/4 top-1 md:invisible">
                        <button class="bg-red-600 rounded-xl w-16 h-4 uppercase">
                            <span class="not-sr-only">Go!</span>
                        </button>
                    </div>
                    <!-- remember remember, this button is for mobile -->
                </div>
            </div>
            <!-- wildcard is NOT Hovered -->
            <div id="city-info" class="z-10 relative text-lightText flex flex-col h-full justify-around" onmouseover="cityWildcardHovered(this.id, true)" onmouseleave="cityWildcardHovered(this.id, false)">
                <div class="flex justify-between">
                    <!-- adds a blank space so that the overall score will always be on the right. DO NOT REMOVE! -->
                    <div id="notTrending"></div>
                    <div id="isTrending" class="relative left-1.5 bg-custom-red rounded-3xl w-20 h-4 top-2 md:top-auto">
                        <h4 class="uppercase text-xs text-center">
                            <b class="not-sr-only">Trending</b>
                        </h4>
                    </div>
                    <div class="relative right-2.5 text-center md:-top-1.5">
                        <span class="sr-only">Overall Score:</span>
                        <h5 id="overall-score" class="not-sr-only" style="font-family: 'Open Sans', serif">
                        </h5>
                        <svg class="relative -top-1" width="25" height="1" viewBox="0 0 25 1" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line
                                    id="overall-score-line"
                                    y1="0.5"
                                    x2="25"
                                    y2="0.5"
                            />
                        </svg>
                    </div>
                </div>

                <div class="inline-grid text-center">
                    <h3 id="city-name" class="not-sr-only"></h3>
                    <h4 id="country-name" class="relative -top-2.5 text-subtitleGray not-sr-only"></h4>
                </div>

                <div class="relative inline-flex left-2.5 md:top-4">
                    <svg width="32" height="32" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="rainy" style="display:none;" />
                        <path
                                d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7"
                                class="rainy"
                                style="display:none;"
                        />
                        <path d="M11 13v2m0 3v2m4 -5v2m0 3v2" class="rainy" style="display:none;" />
                        <!--sunny below, rainy up -->
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="sunny" style="display:none;" />
                        <circle cx="12" cy="12" r="4" class="sunny" style="display:none;" />

                        <path
                                d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"
                                class="sunny"
                                style="display:none;"
                        />

                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="stormy" style="display:none;" />
                        <path
                                d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"
                                class="stormy"
                                style="display:none;"
                        />
                        <polyline points="13 14 11 18 14 18 12 22" class="stormy" style="display:none;" />

                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="foggy" style="display:none;" />
                        <path
                                d="M7 16a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-12"
                                class="foggy"
                                style="display:none;"
                        />

                        <line x1="5" y1="20" x2="19" y2="20" class="foggy" style="display:none;" />
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="snowy" style="display:none;" />
                        <path
                                d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7"
                                class="snowy"
                                style="display:none;"
                        />
                        <path d="M11 15v.01m0 3v.01m0 3v.01m4 -4v.01m0 3v.01" class="snowy" style="display:none;" />

                        <path stroke="none" d="M0 0h24v24H0z" fill="none" class="cloudy" style="display:none;" />
                        <path
                                d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-12"
                                class="cloudy"
                                style="display:none;"
                        />
                    </svg>

                    <span class="sr-only">The weather is {{ weatherType }}</span>
                    <h4 id="city-temperature" class="m-1 not-sr-only">
                        <span class="sr-only">The temperature is </span>
                    </h4>
                </div>
            </div>
            </a>
        </div>
    </div>
    <form action="added.php" method="post" <?php if ($type != 'city' || !isset($_SESSION['userSession'])) {echo 'dispaly:none;';}?>>
        <label>
            <input name="<?php if ($isFavourite) {echo 'remove-favourite';}else{echo 'add-favourite';}?>" value="lol" style="display: none"/>
        </label>
        <label>
            <input name="cityID" value="<?php echo $searchQuery; ?>"  style="display: none" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
        </label>
        <button type="submit" class="flex items-center justify-center focus:outline-none text-custom-ghost text-sm sm:text-base bg-custom-cayola hover:bg-custom-evil-cayola rounded py-2 w-full transition duration-150 ease-in <?php if ($type != 'city' || !isset($_SESSION['userSession'])){echo 'opacity-50 cursor-not-allowed';?>" <?php echo 'disabled';} ?>>
            <?php if ($isFavourite) {echo 'Remove from Favourites';}else{echo 'Add to Favourites';}?>
        </button>
    </form>

    <button type="submit" class="flex items-center justify-center focus:outline-none text-custom-ghost text-sm sm:text-base bg-custom-cayola hover:bg-custom-evil-cayola rounded py-2 w-full transition duration-150 ease-in <?php if ($type != 'city' || !isset($_SESSION['userSession'])){echo 'opacity-50 cursor-not-allowed'; ?>" onclick="$('#review-inserter').show('fast')" <?php echo 'disabled';} if ($type != 'city' || !isset($_SESSION['userSession'])) {echo 'dispaly:none;';}?>>
    Insert review
    </button>
</div>
    <div id="review-inserter" class="modal z-40 absolute w-full h-full top-0 left-0 flex items-center justify-center" style="display: none">
        <div class="modal-overlay absolute w-full h-full bg-black opacity-25 top-0 left-0 cursor-pointer z-0"></div>
        <div class="absolute flex flex-col bg-custom-dark shadow-lg px-4 sm:px-6 md:px-8 lg:px-10 py-8 rounded-md w-full max-w-md">
            <div class="font-medium self-center text-xl sm:text-2xl uppercase">Login To Your Account</div>
            <div class="mt-10">

                <form action="added.php" id="review-form" method="post">
                    <label>
                        <input name="cityID" value="<?php echo $searchQuery; ?>" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" style="display: none" />
                    </label>
                    <div class="flex flex-col mb-6">
                        <label for="review-taxes" class="mb-1 text-xs sm:text-sm tracking-wide">Taxes</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-taxes" required="required" type="number" min="1" max="100" name="review-taxes" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex flex-col mb-6">
                        <label for="review-taxes-text" class="mb-1 text-xs sm:text-sm tracking-wide">Taxes review</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-taxes-text" type="text" name="review-taxes-text" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex flex-col mb-6">
                        <label for="review-COL" class="mb-1 text-xs sm:text-sm tracking-wide">Cost of Life</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-COL" required="required" type="number" min="1" max="100" name="review-COL" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex flex-col mb-6">
                        <label for="review-COL-text" class="mb-1 text-xs sm:text-sm tracking-wide">Cost of Life review</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-COL-text" type="text" name="review-COL-text" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex flex-col mb-6">
                        <label for="review-environment" class="mb-1 text-xs sm:text-sm tracking-wide">Environment</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-environment" required="required" type="number" min="1" max="100" name="review-environment" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex flex-col mb-6">
                        <label for="review-environment-text" class="mb-1 text-xs sm:text-sm tracking-wide">Environment review</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-environment-text" type="text" name="review-environment-text" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex flex-col mb-6">
                        <label for="review-security" class="mb-1 text-xs sm:text-sm tracking-wide">Security</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>

                            <input id="review-security" required="required" type="number" min="1" max="100" name="review-security" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex flex-col mb-6">
                        <label for="review-security-text" class="mb-1 text-xs sm:text-sm tracking-wide">Security review</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
                                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="review-security-text" type="text" name="review-security-text" class="text-sm sm:text-base pl-10 pr-4 rounded-lg bg-custom-eerie w-full py-2 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex flex-col mb-6">
                        <label for="overall-evaluation" class="mb-1 text-xs sm:text-sm tracking-wide">Overall Evaluation</label>
                        <div class="relative">
                            <div class="inline-flex items-center justify-center absolute left-0 top-0 h-full w-10">
              <span>
                <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                  <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
                            </div>
                        <input id="overall-evaluation" required="required" type="text" name="overall-evaluation" class="text-sm sm:text-base pl-10 pr-4 rounded-lg   bg-custom-eerie w-full py-2 focus:outline-none"/>
                        </div>
                    </div>
                    <div class="flex w-full">
                        <button name="review-insert" type="submit" class="flex items-center justify-center focus:outline-none text-custom-ghost text-sm sm:text-base bg-custom-cayola hover:bg-custom-evil-cayola rounded py-2 w-full transition duration-150 ease-in">
                            <span class="mr-2 uppercase">Submit</span>
                            <span>
                                  <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                  </svg>
                                </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="cards-loader" class="grid justify-center" style="display: none">
        <div class="relative justify-center w-12 h-12 border-8 border-custom-cayola rounded-full loader"></div>
    </div>
</div>
</body>
</html>