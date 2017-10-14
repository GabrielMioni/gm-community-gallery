=== GM Community Gallery ===
Contributors: Gabriel Mioni
Tags: gallery, front-end, uploader, image, .gif, .png, .jpg, .jpeg, Javascript, Ajax
Requires at least: 3.0.1
Tested up to: 4.8.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An image gallery and file repository plugin for WordPress. Perfect for users who want to curate image submissions from the public.

== Description ==

GM Community Gallery allows WordPress users to administer a gallery and accept images from site visitors. It requires zero configuration to start using immediately.

There's three basic parts to the GM Community Gallery:
1. GM Submit Form: This is where your site visitors can upload images.
2. GM Public Gallery: Where uploaded images are displayed.
3. GM Community Gallery Admin: When you administer and make changes to uploaded images. Also (optional) settings!

To create a GM Submit Form on a WordPress Page:
- Use the shortcode [gm-submit-form][/gm-submit-form]

To create a GM Public Gallery on a WordPress Page:
- Use the shortcode [gm-public-gallery][/gm-public-gallery]

Once the GM Community Gallery plugin is installed, the admin panel is found in the beneath 'Settings' in WordPress Admin.


== Features ==
1. Attractive public facing uploader allows site visitors to upload .jpg/.jpeg, .png and .gif images with a message. Submission data is validated and processed with Ajax. If JavaScript is disabled, a non-Ajax form is displayed instead.
2. Administrative tools the gallery administrator can use to edit/view image data and move images to 'trash' before choosing to permanently delete images.
3. Public gallery JavaScript lightbox that lets viewers navigate images and view image messages. On desktop the lightbox can be navigated with mouse clicks or keyboard. On mobile the lightbox is navigated by touch and swipes.


== Installation ==

For manual installation:

1. Upload the gm-community-gallery folder and its contents to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

That is it. Once the plugin is installed and your shortcodes are in place you are ready to start receiving and displaying images in your new gallery.


== Gallery Admin ==

I. Gallery Options.

Configuration (like many good things in the world) is optional.

To access the the GM Gallery Options:

- From the WordPress Admin Panel, hover or click 'GM Community Gallery' and select > 'Gallery Options'

Here you can set the GM Community Gallery to do some things:

1. Send Notification - If this is enabled, an email will be sent every time someone submits an image. By default this will be the email address associated with the WordPress administrator, but you can set a different email address in the 'Notification Email' field.

2. Max Image Size (kb): Specifies the maximum file size you want to allow. By default, GM Community Gallery will accept files up to 500 kilobytes. You can raise or lower that value here.

3. Images per page: Sets how many images you would like to display per page. GM Community Gallery includes a page navigation bar with the gallery. By default, the gallery will display 10 images per page.

4. Banned IPs: Folks stepping out of line? You may banish them by adding their IP addresses to this this textarea.


II. Admin and Trash Galleries

When you click on GM Community Gallery from the WordPress admin panel, you'll see both Admin and Trash galleries in the left navigation panel. Each includes an image count in the title.

1. Admin Gallery:

Here you can search for and navigate the images in your gallery. Percentage signs [ % ] can be used as wildcards (e.g., searching 'Bill%' will find both 'Billie' and 'Billy').

Clicking on an image will bring you to the gallery edit screen where you can review and make changes to image metadata (including Title, Submitter Name, Submitter Email and IP).

You can also both edit the message the image submitter included with their upload and post/edit a reply. Replies will be displayed in the Public Gallery lightbox and image view pages.

Multiple images can be selected and sent to 'Trash' either by individually clicking the checkbox for chosen images or using the 'Select All' checkbox (after which individual images can be de-selected).

2. Trash Gallery:

Once an image is in the Trash Gallery it will no longer be displayed in the Public Gallery. From here, you have the option to permanently delete an image and its associated meta data (including data about the submitter and any message/reply content).

If you have second thoughts, you can also move images back to the Public Gallery.

Both 'permanently delete' and 'move back to gallery' options are managed by clicking the checkboxes for images on which you want the action to apply and then clicking the 'Submit' button. Like the regular admin gallery, a 'check all' checkbox is available.

== Changelog ==

= 1.0 =
* Initial Release