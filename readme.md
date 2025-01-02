<a name="readme-top"></a>


<!-- PROJECT LOGO -->
<br />
<div align="center">

<h3 align="center">jolpica2Ergast</h3>

  <p align="center">
    Library to populate the Ergast database structure from the Jolpica API.
    <br />
  </p>
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="#acknowledgments">Acknowledgments</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

For years I have been happily using the Ergast database dumps containing all the F1 results data since its first race in 1950. Unfortunately, it was announced that Ergast was ceasing to be maintained and updated from the end of 2024, leaving me searching for an alternative. Jolpica is an almost direct replacement for Ergast except that it no longer supplies database dumps. Therefore, I have written this script to query the Jolpica API and write the results back to an Ergast database format.

<a href='https://ko-fi.com/Y8Y0POEES' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi5.png?v=6' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

![](https://www.spokenlikeageek.com/wp-content/uploads/2025/01/2025-01-02-13-31-12.png)

<p align="right">(<a href="#readme-top">back to top</a>)</p>



### Built With

* [PHP](https://php.net)
* [Jolpica](https://github.com/jolpica/jolpica-f1)
* [Ergast](https://ergast.com/mrd/)

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- GETTING STARTED -->
## Getting Started

Running the script is very straightforward:

1. download the code/clone the repository

### Prerequisites

Requirements are very simple, it requires the following:

1. PHP (I tested on v8.1.13)

### Installation

1. Clone the repo:
   ```sh
   git clone https://github.com/williamsdb/pjolpica2Ergast.git
   ```

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- USAGE EXAMPLES -->
## Usage

It can be run either from the command line or as a web page (although I wouldn't recommend this) and takes a two parameters as follows:

* 'cmd' which can be set to 'STATIC', 'RACE' or 'ALL'. If no parameter is passed, it defaults to 'ALL'.
* 'year' the four digit year you want to run for. If not passed the script defaults to the current year.

For example:

```./php index.php race 2024```

or

```./php index.php all```


<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- ROADMAP -->
## Known Issues

See the [open issues](https://github.com/williamsdb/jolpica2ergast/issues) for a full list of proposed features (and known issues).

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- LICENSE -->
## License

Distributed under the GNU General Public License v3.0. See `LICENSE` for more information.

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTACT -->
## Contact

Bluesky - [@spokenlikeageek.com](https://bsky.app/profile/spokenlikeageek.com)

Mastodon - [@spokenlikeageek](https://techhub.social/@spokenlikeageek)

X - [@spokenlikeageek](https://x.com/spokenlikeageek) 

Website - [https://spokenlikeageek.com](https://www.spokenlikeageek.com/tag/jolpica2ergast/)

Project link - [Github](https://github.com/williamsdb/jolpica2ergast)

<p align="right">(<a href="#readme-top">back to top</a>)</p>


<!-- ACKNOWLEDGMENTS -->
## Acknowledgments

* [jolpica](https://github.com/jolpica/jolpica-f1)

<p align="right">(<a href="#readme-top">back to top</a>)</p>


[![](https://github.com/williamsdb/jolpica2Ergast/graphs/contributors)](https://img.shields.io/github/contributors/williamsdb/jolpica2Ergast.svg?style=for-the-badge)

![](https://img.shields.io/github/contributors/williamsdb/jolpica2Ergast.svg?style=for-the-badge)
![](https://img.shields.io/github/forks/williamsdb/jolpica2Ergast.svg?style=for-the-badge)
![](https://img.shields.io/github/stars/williamsdb/jolpica2Ergast.svg?style=for-the-badge)
![](https://img.shields.io/github/issues/williamsdb/jolpica2Ergast.svg?style=for-the-badge)
<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/williamsdb/jolpica2Ergast.svg?style=for-the-badge
[contributors-url]: https://github.com/williamsdb/jolpica2Ergast/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/williamsdb/jolpica2Ergast.svg?style=for-the-badge
[forks-url]: https://github.com/williamsdb/jolpica2Ergast/network/members
[stars-shield]: https://img.shields.io/github/stars/williamsdb/jolpica2Ergast.svg?style=for-the-badge
[stars-url]: https://github.com/williamsdb/jolpica2Ergast/stargazers
[issues-shield]: https://img.shields.io/github/issues/williamsdb/jolpica2Ergast.svg?style=for-the-badge
[issues-url]: https://github.com/williamsdb/jolpica2Ergast/issues
[license-shield]: https://img.shields.io/github/license/williamsdb/jolpica2Ergast.svg?style=for-the-badge
[license-url]: https://github.com/williamsdb/jolpica2Ergast/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/linkedin_username
[product-screenshot]: images/screenshot.png
[Next.js]: https://img.shields.io/badge/next.js-000000?style=for-the-badge&logo=nextdotjs&logoColor=white
[Next-url]: https://nextjs.org/
[React.js]: https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB
[React-url]: https://reactjs.org/
[Vue.js]: https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D
[Vue-url]: https://vuejs.org/
[Angular.io]: https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white
[Angular-url]: https://angular.io/
[Svelte.dev]: https://img.shields.io/badge/Svelte-4A4A55?style=for-the-badge&logo=svelte&logoColor=FF3E00
[Svelte-url]: https://svelte.dev/
[Laravel.com]: https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white
[Laravel-url]: https://laravel.com
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com 
