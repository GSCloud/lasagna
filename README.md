# **1\. Tesseract LASAGNA: Modern MVP CMS Framework**

Tesseract LASAGNA is a **Model View Presenter (MVP) CMS framework** designed for rapid development and high performance. It uniquely leverages **Google Sheets for its basic database model and data management**, simplifying data handling and providing a flexible foundation.

This framework is built with a focus on **scalability and a sessionless architecture**, making it ideal for modern web applications, including **Progressive Web Apps (PWAs)**.

## **1.1. Key Features**

* **Data Management**:  
  * **Google Sheets Data**: Utilizes Google Sheets for defining its Model and managing data, simplifying content updates and integration.  
* **Authentication & Security**:  
  * **Google OAuth 2.0 Authentication**: Secure user login via Google's robust OAuth 2.0 standard.  
  * **CSP Security Headers**: Enhances security by mitigating content injection attacks.  
  * **Rate Limiter**: Protects against abuse and brute-force attacks by controlling request frequency.  
  * **Country Blocking**: Integrates with Cloudflare for geographical access restrictions.  
* **Development & Deployment**:  
  * **Makefile**: Streamlines development and deployment tasks with a comprehensive set of commands.  
  * **Docker Container**: Planned official Docker image release for easy deployment and portability.  
  * **PHP 7.4+ & Composer**: Built on modern PHP and managed with Composer for efficient dependency handling.  
  * **CLI Support**: Extensive command-line interface for managing nearly every framework function.  
  * **Redis Support**: Optional support for Redis for enhanced caching performance.  
* **Front-End & User Experience**:  
  * **HTML5 Design**: Modern web design foundation.  
  * **Materialize CSS Framework**: Provides a responsive and elegant user interface.  
  * **Font Awesome Icons**: Integrates a rich library of vector icons.  
  * **Responsive Design**: Ensures optimal viewing across various devices.  
  * **Progressive Web App (PWA)**: Designed for rapid prototyping of high-performance PWAs.  
  * **Custom Service Worker**: Enhances PWA capabilities.  
  * **Multi-language Support**: Built-in functionality for multilingual applications.  
  * **In-page WYSIWYG Summernote Editor**: For intuitive content creation directly within the application.  
  * **GSC Object (JavaScript)**: Custom JavaScript object for client-side interactions.  
  * **Cloudflare CDN Support**: Optimizes content delivery.  
  * **Google Maps Support**: Easy integration of mapping functionalities.  
  * **Umami Analytics Support**: For privacy-focused web analytics.  
* **Backend & API**:  
  * **In-page Administration Panel**: Provides a streamlined interface for managing the application.  
  * **REST API**: Dynamically generated API for flexible data access and integration.  
  * **Remote Function Calls**: Enables secure remote operations via the AdminPresenter.

