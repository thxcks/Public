<!-- Improved compatibility of back to top link: See: https://github.com/othneildrew/Best-README-Template/pull/73 -->
<a id="readme-top"></a>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/thxcks/WP-tools">
    <img src="https://i.ibb.co/SXjCbVw/wp-toolkits-nobg.png" alt="Logo" width="150" height="150">
  </a>

<h3 align="center">Shared Hosting Self Help Tools</h3>

  <p align="center">
A collection of tools designed to help users independently manage and troubleshoot common (WordPress) issues on shared hosting environments. Built to address typical WordPress tasks without the need for plugins, these tools optimize performance and avoid the bloat, slowdowns, and security risks often introduced by plugin-based solutions. This toolset empowers users to manage their sites efficiently, maintaining a lean, secure WordPress installation without relying on direct hosting provider intervention.  
<br>
<br>

<a href="https://krystal.io/" style="display: block; text-align: center;">
<img src="https://krystal.io/_next/static/media/logo.e7b0e828.svg" alt="Logo" style="display: block; margin: 0 auto;" width="250" height="150">
</a>

<p>Designed to be compatible with Krystal Hosting Packages!</p>


</p>


</div>




<!-- GETTING STARTED -->
## Getting Started

Most scripts are designed to run directly in your WordPress installation's document root. Simply upload the file, then access it via your browser to begin using its functionality.

The script records your IP address during its initial run and restricts access exclusively to that IP until the 'ip_lock.lock' file is removed either manually or by using the "Destroy" Button.

> [!WARNING]  
> These scripts are designed to be temporary, with minimal IP protection as a basic deterrent. Not secure for extended online use—remove after use.
>
> Each script has a Destroy button to remove the tool and corresponding files after use!

## List of Scripts and Usage

In the example, we use `wget` to download the tools, but you’re free to download and upload them using any method that suits your workflow.



<h2>File/Folder Permission Manager</h2>
<p>Easily browse your file structure and set folder permissions to 755 and file permissions to 644 in bulk.</p>

> [!TIP]
> exec() is required for this script.

  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/permissions.php
  ```

<h2>Access Log Viewer</h2>
<p>This PHP script provides an interface to view and analyze server log files. It allows users to filter, sort, and paginate logs, making it easier to reead access logs.</p>

  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/log-viewer.php
  ```

<h2>Email Header Analyzer</h2>
<p>This PHP script provides an interface to view and analyze Email Headers, shows important information as well as email hops</p>

  ```sh
  wget https://raw.githubusercontent.com/thxcks/Shared-Toolkit/refs/heads/main/php/analyzer.php
  ```

<h2>File Usage/Inode Usage</h2>
<p>Provides detailed insights into your current disk usage and inode consumption.</p>

  ```sh
 wget https://raw.githubusercontent.com/thxcks/Shared-Toolkit/refs/heads/main/php/files.php
  ```

<h2>Server Information Tool</h2>
<p>This tool provides detailed server and PHP environment information, tests database connectivity, and displays key database details, helping diagnose and optimise hosting setups efficiently.</p>

  ```sh
 wget https://raw.githubusercontent.com/thxcks/Shared-Toolkit/refs/heads/main/php/info.php
  ```

<h2>Wordpress MYSQL Process Viewer</h2>
<p>View real-time MySQL processes for your WordPress database with automatic refresh and control options.</p>

  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/processes.php
  ```

<h2>Wordpress User Management Portal</h2>
<p>Uses WP-CLI to manage the users in the database - includes, create, delete and password reset functions.</p>

> [!TIP]
> shell_exec & escapeshellarg are required for this script.


  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/users.php
  ```

<h2>Wordpress Simple Backup Script</h2>
<p>This tool creates a downloadable backup of your website files and database, with options to exclude wp-content or uploads.</p>

> [!TIP]
> shell_exec or exec & escapeshellarg are required for this script.


  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/backup.php
  ```

<h2>Wordpress Domain Changer</h2>
<p>This tool changes the domain name in your Wordpress database including Table updates.

Can also swap http:// to https:// without changing the domain name</p>

> [!TIP]
> shell_exec or exec & escapeshellarg are required for this script.


  ```sh
  wget https://raw.githubusercontent.com/thxcks/WP-tools/refs/heads/main/php/changeurl.php
  ```

<h2>Wordpress Plugin Tester</h2>
<p>Disables each plugin in your installation one by one until the plugin conflict is resolved.</p>

  ```sh
  wget https://raw.githubusercontent.com/thxcks/Public/refs/heads/main/php/plugin-tester.php
  ```


<p align="right">(<a href="#readme-top">back to top</a>)</p>


<!-- ROADMAP -->
## Tool Roadmap

- [ ] Reinstall Core WP
- [ ] Backup/Restore
- [ ] Locate and manage multiple WP Insalls
- [ ] Many More


<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, simply open an issue with the tag "enhancement".

Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#readme-top">back to top</a>)</p>
