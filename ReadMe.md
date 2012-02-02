#Tumblr
##Plugin for Wolf CMS & Frog CMS created by [A.J. Cates](http://ajcates.com)

Tumblr allows you to display posts from tumblr.com on your Wolf CMS or Frog CMS site.

	tumblrPosts($username, $page);

Set `$username` to what ever your tumblr username is, and $page to the page you would like return.

	printTumblrPosts($username, $page);

You can also use `printTumblrPosts()` to print the posts directly to the screen.

	tumblrInfo($username);

The `tumblrInfo()` function will return an associative array with information from the tumblr of `$username`.

	tumblrPost($username, $id);

The `tumblrPost()` function will return a single tumblr post array object.

	printTumblrPost($username, $id);

The `printTumblrPost()` function will print a single tumblr post.