# plesk-microweber-plugin

## What is Microweber Plesk Plugin?

Microweber Plesk Plugin is a Plesk extension that allows you to install Microweber CMS on your Plesk server.

Microweber is a drag and drop website builder and content management system of new generation. The main features of Microweber are easy Drag and Drop technology, the real time text writing and editing.

You can create any kind of website, blog or online store with Drag and Drop technology without need of any coding.
It contains 450 + pre-designed layouts in 20 categories, which are mobile ready and ready for use with a single click of the mouse.
Module based architecture with 75+ modules allows you to expand unlimited your website.
[Microweber Website Builder Video](https://youtu.be/JwUj6mGZ20I "Short Video of how it's work")




## Features of Microweber Plesk Plugin

- Unlimited websites creation
- Modern and intuitive control panel with admin and live edit mode
- Change Logo and branding
- Own links on the templates
- WHMCS Plugin
- Automatic Installation



[![Microweber](https://microweber.com/cdn/partners/plesk/live.jpg)](https://youtu.be/EKiaLcZkReM)

#### 450+ Predefined Layouts in 20 categories (ready for click and drop)
- Titles (8)
- Text Block (15)
- Content (72)
- Features (43)
- Gallery (23)
- Call To Action (22)
- Blog (13)
- Team (16)
- Testimonials (20)
- Contact Us (17)
- Grids (14)
- Misc.. (11)
- Price Lists (7)
- Video (7)
- Ecommerce (8)
- Header (25)
- Menu (7)
- Footers (28)
- Other (3)
- Default layouts (26)


#### 60+ Modern Templates

[![Microweber](https://microweber.com/cdn/partners/plesk/templates2.jpg)](https://youtu.be/EKiaLcZkReM)

- Professional templates each of it contains more than 450+ layouts
- Fully responsive templates
- Last design trends
- New templates are adding each week


#### 75+ Modules

[![Microweber](https://microweber.com/cdn/partners/plesk/modules.jpg)](https://youtu.be/EKiaLcZkReM)
- Upload Images
- Text and Paragraphs
- Headings
- Galleries
- Contact Forms
- Google Maps
- Social Medias
- Videos
- Products
- Registration
- Backups
- Standalone updater
- Taxes
- Discount
- And may more...

#### Fully Customizable Web Templates and Layouts

[![Microweber](https://microweber.com/cdn/partners/plesk/visual-editor.jpg)](https://youtu.be/EKiaLcZkReM)

- Change anything of your website or Layout
- Color scheme
- Font family
- Icon Sets (1000+ icons integrated)
- Layout grids and sizes
- Borders
- Add spacing
- Control typography
- and more


#### Full Online Store functionality
- Adding products (with galleries)
- Track orders (detailed information about the order and customer)
- Customer information
- Adding discounts of the products (products on sale)
- 10+ famous payment methods
- Shipping settings
- Custom Fields for each product
- Auto respond order email
- Products variants (coming soon)
- Full control of product display (search, filters, tags, rating, reviews, etc.)
- Statistics


[![Online Store Microweber](https://microweber.com/cdn/partners/plesk/shop-settings-microweber.jpg)](https://youtu.be/EKiaLcZkReM)




# Microweber Video

No-code platform for creating websites of all sorts. Make it easy, with 450 + pre-designed layouts created for you.

[![Microweber video](https://microweber.com/cdn/partners/plesk/video-youtube.jpg)](https://youtu.be/EKiaLcZkReM)


# How to install the plugin

## Automatic installation
1. Open Plesk Panel
2. Go to Extensions Catalog and install the "Microweber" extension


## How to use


**For automatic website creation**: In order to make automatic install when the user creates new domain, you must go to *Home->Service Plans->Hosting Plans* and then click on *Additional Services* and select *Install Microweber* from the dropdown box.

![plan.png](https://microweber.com/cdn/partners/plesk/plan.png "")



**For manual website creation**: Click the Microweber icon in the sidebar under *Server management->Microweber* and then click on *Install* and select *Domain* from the dropdown box.

![plan.png](https://microweber.com/cdn/partners/plesk/install.png "")


## Settings

**For plugin setup**: Go to *Server management->Microweber->Settings* and you will be able to set various options of the plugin and also connect it to WHMCS.

![plan.png](https://microweber.com/cdn/partners/plesk/settings.png "")


## Templates download and Updates


**For templates setup**: Go to *Server management->Microweber->Versions* and you will be able to update the plugin and download templates

![plan.png](https://microweber.com/cdn/partners/plesk/versions.png "")


#  Web server setting


If your server is slow you can improve the speed by editing some server setting

### Nginx setting
-  open the created /etc/nginx/conf.d/directives.conf file in a text editor


```
nano /etc/nginx/conf.d/directives.conf
```

-  Add required directives. For example:
```
proxy_buffer_size          128k;
proxy_buffers              4 256k;
proxy_busy_buffers_size    256k;
```

### Folders where the plugin are installed

Folders where the plugin will be installed:

```
/opt/psa/admin/sbin/modules/microweber
/usr/local/psa/admin/plib/modules/microweber
/usr/local/psa/admin/share/modules/microweber
/usr/local/psa/var/modules/microweber
```
