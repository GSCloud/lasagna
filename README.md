# **Tesseract LASAGNA**

## **Modern MVP CMS Framework**

Tesseract LASAGNA is a high-performance, Model View Presenter (MVP) CMS framework built for rapid development of modern web applications, including Progressive Web Apps (PWAs). It uniquely leverages Google Sheets for its data model and management, offering unparalleled flexibility and ease of use with a scalable, sessionless architecture.

## **Key Features**

### **Data Management**

* **Google Sheets Data:** Utilizes Google Sheets as a core component for defining its data model and managing content. This approach simplifies content updates, enables real-time team collaboration, and streamlines integration with existing Google Workspace workflows, leveraging familiar spreadsheet interfaces.  
* **Flexible Data Structures:** Supports highly adaptable data structures, allowing easy definition and modification of content types directly within Google Sheets, including automatic translations and variables. This ensures your CMS can rapidly evolve with project needs, eliminating complex database migrations and fostering agility, while also allowing for granular Google permissions for review and edit access.  
* **Real-time Synchronization:** Data changes in Google Sheets are automatically synchronized with the CMS, ensuring your website content is always up-to-date and reflecting collaborative edits instantly.  
* **String Filters (SF) Library:** Incorporates a powerful String Filters (SF) library for advanced string replacements and manipulations. This library provides robust support for Czech, Slovak, and English languages, with beta support for German, ensuring accurate and localized content processing, often bound to the data model for dynamic content.  
* **ShortCodes Support:** Includes comprehensive support for ShortCodes, enabling content creators to embed dynamic content, complex layouts, or interactive elements directly within their text using simple, easy-to-use tags, which are also often tied to the data model for content rendering.  
* **Cell-based Integrations:** Supports up to 30 distinctive generated integrations within a single Google Sheet cell, allowing for highly condensed and powerful data definitions directly within your content.  
* **Custom NE-ON Replacements:** Provides support for custom text replacements and transformations using NE-ON definitions, offering advanced control over content rendering and dynamic data manipulation.

### **Authentication & Security**

* **Google OAuth 2.0 Authentication:** Secure user login via Google's robust OAuth 2.0 standard.  
* **Halite Encrypted Passport:** Employs Halite for encrypted passport authentication, providing a secure, sessionless mechanism for user verification without relying on traditional session cookies.  
* **CSP Security Headers:** Enhances security by mitigating content injection attacks.  
* **Rate Limiter:** Protects against abuse and brute-force attacks by controlling request frequency, **with automatic banning to prevent content scraping and other malicious activities.**  
* **Country Blocking:** Integrates with Cloudflare for robust geographical access restrictions, enhancing security and compliance by controlling where your content can be accessed.

### **Development & Deployment**

* **Database-less & Sessionless Architecture:** Operates without a traditional database or session cookies, relying entirely on a robust caching mechanism (filesystem, Redis) for data retrieval. This design ensures high performance and is inherently ready for vertical and horizontal scaling.  
* **Makefile:** Streamlines development and deployment tasks with a comprehensive set of commands.  
* **Docker Container:** Planned official Docker support for easy deployment and portability (soon).  
* **PHP 7.4+ & Composer:** Built on modern PHP 7.4+ and managed with Composer, ensuring a stable, maintainable, and easily extensible codebase for developers.  
* **CLI Support:** Extensive command-line interface for managing nearly every framework function.  
* **Redis Support:** Optional support for Redis Database for enhanced caching performance.

### **Front-End & User Experience**

* **HTML5 Design:** Modern web design foundation.  
* **Materialize CSS Framework:** Provides a responsive and elegant user interface.  
* **Font Awesome Icons:** Integrates a rich library of vector icons.  
* **Responsive Design:** Ensures optimal viewing across various devices.  
* **Progressive Web App (PWA):** Designed for rapid prototyping of high-performance PWAs, offering **installable capabilities, robust offline support, and seamless web sharing on mobile devices.**  
* **Custom Service Worker:** Enhances PWA capabilities.  
* **Multi-language Support:** Built-in functionality for **unlimited languages**, with administration available in **English**, **Czech**, **Slovak**, and **German** (beta).  
* **Powerful Router:** Features a sophisticated routing system with automatic language switching via **Cloudflare Country** detection, support for aliases, dynamic template switching, and the ability to attach unlimited custom data to any route, providing unparalleled flexibility for content delivery.  
* **In-page WYSIWYG Summernote Editor:** For intuitive content creation directly within the application.  
* **GSC Object (JavaScript):** Custom JavaScript object for client-side interactions.  
* **Cloudflare CDN Support:** Optimizes content delivery.  
* **Google Maps Support:** Easy integration of mapping functionalities.  
* **Umami Analytics Support:** For privacy-focused web analytics.  
* **Social & Media Integrations:** Seamlessly supports embedding and linking content from popular platforms including **Twitch**, **SoundCloud**, **Mastodon**, **YouTube**, and **Vimeo**, enriching your site with diverse media.  
* **Generated Image Galleries:** Automatically creates responsive and visually appealing image galleries from specified content, simplifying media presentation and enhancing user engagement.

### **Backend & API**

* **In-page Administration Panel:** Provides a streamlined interface for managing the application.  
* **REST API:** Dynamically generated API for flexible data access and integration.  
* **Remote Function Calls:** Enables secure remote operations.

