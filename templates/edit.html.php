<div id="edit_article">
    <?php
    if (isset($key)) {
        include '_picvalidation.html.php';
    }
    if (!empty($message)) { ?>
        <h4><tt><?= $message; ?></tt></h4>
    <?php } ?>

    <form action="<?= $action ?>" method="post">
        <fieldset>
            <label for="title">title</label><input type="text" name="title" id="title" placeholder="name of the article" required autofocus maxlength="100" minlength="3" value="<?= $article->title ?? $_title ?>" pattern="[A-Za-z !,'.]+">
            <label for="page">page</label>
            <?php
            if (!empty($select['options'])) { ?>
                <?php
                include_once '_select.html.php';
                ?>
            <?php } else { ?>
                <input type="text" name="page" id="page" placeholder="associated page" required autofocus maxlength="255" value="<?= $article->page ?? $_page ?>">

            <?php  }
            ?>
            <div id="order">
                <label for="position">position (max:<?= $max;?>)</label>
                <input name="position" id="position" type="number" max="<?= $max ?>" min="0" value="0" />
                <label for="shuffle">shuffle</label>
                <input name="shuffle" id="shuffle" type="checkbox" checked />
                <?php
                if (isset($article->page)) { ?>
                    <input type="hidden" name="mypage" value="<?= $article->page ?? '' ?>" />
                    <input type="hidden" name="mytitle" value="<?= $article->title ?? '' ?>" />
                <?php }
                if ($key === 'headsup') { ?>
                    <input type="hidden" name="override" value="override" />
                <?php  }
                ?>
            </div>
            <label for="pubdate">published</label>
            <input type="date" name="pubdate" id="pubdate" placeholder="YYYY-MM-DD" required maxlength="10" value="<?= $article->pubdate ?? $_date ?>">
            <label for="attr_id">meta_data</label>
            <input name="attr_id" id="attr_id" maxlength="30" value="<?= $article->attr_id ?? '' ?>" pattern="[a-z.]+">
            <label for="summary">summary</label>
            <textarea name="summary" id="summary" placeholder="description/comments" maxlength="1000" style="height: 3em;"><?= $article->summary ?? '' ?></textarea>

            <label for="tx">content</label>
 <?php if (isset($_COOKIE['js']) && !empty($_COOKIE['js'])) {
                include '_controls.html.php';
                include 'markdown_guide.html.php';
            } else {
                include 'markdown_guide.html';
            }
            ?>
            <textarea name="content" id="tx" placeholder="The HTML content of the article" maxlength="200000" style="height: 20em;"><?= $article->content ?? $_content ?></textarea>
            <?php
            if (!empty($article->id)) { ?>
                <input type="hidden" name="pk" value="<?= $article->id; ?>">
            <?php } ?>
        </fieldset>
        <input type="submit" name="action" value="submit">
    </form>
</div>
<div class="remplacer">
    <?php
    if (!empty($article->id)) { ?>
        <a href="<?= $route; ?>" id="edit_link" title="edit article pics">edit article pics</a>
        <a href="<?= $upload ?>" id="upload_link" title="upload">Add Asset</a>
    <?php
    } else { ?>
        <!--<a href="<?= $upload; ?>" id="upload_link" title="UPLOAD">upload pic</a> -->
    <?php  }
    ?>
    <a href="<?= $exit ?>">Exit</a>
</div>