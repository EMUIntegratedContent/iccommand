<template>
	<div>
		<template v-if="typeof username != 'undefined'">
			<div class="row">
				<div class="col-xs-12">
					<h2>Available Applications</h2>
				</div>
			</div>
			<div class="row">
        <div class="col-xs-12">
          <p v-if="!userHasModules">
            You do not currently belong to any applications.
          </p>
          <template v-else>
            <ModuleCategoryCards
              v-for="category in visibleCategories"
              :key="category.name"
              :modules="category.modules"
              :mdDisplay="category.mdDisplay"
            ></ModuleCategoryCards>
          </template>
        </div>
			</div>
		</template>
		<template v-else>
			<p>Please <a href="login">sign in</a> to begin.</p>
		</template>
	</div>
</template>
<script>
import ModuleCategoryCards from './ModuleCategoryCards.vue'
export default {
	created() {
		// If the user has any roles (or if the user accessing the homepage is logged in)
		if (this.userroles) {
			let self = this
			this.userroles.forEach(function (role) {
				self.registerUserModule(role)
			})
		}
	},
	components: {ModuleCategoryCards},
	props: {
		username: {
			type: String,
			required: false
		},
		userroles: {
			type: Array,
			required: false
		}
	},
	data: function () {
		return {
			// The category groupings and their order mirror the header dropdown menus
			// in base.html.twig. Keep them in sync when adding/moving apps.
			categoryOrder: [
				{ name: 'Campus Safety & Alerts', mdDisplay: 'col-md-6' },
				{ name: 'Maps & Directories', mdDisplay: 'col-md-6' },
				{ name: 'Academic Marketing', mdDisplay: 'col-md-4' },
				{ name: 'Requests & Operations', mdDisplay: 'col-md-12' },
				{ name: 'Webmaster Tools', mdDisplay: 'col-md-4' }
			],
			userModules: {
				map: {
					title: "Campus Map",
					description:
						"The campus map application contains all points of interest at EMU. These items are displayed at <a href=\"https://www.emich.edu/maps\" target=\"_blank\">emich.edu/maps</a>.",
					buttonText: "Open Application",
					buttonLink: "/map",
					display: false,
          category: 'Maps & Directories'
				},
				redirect: {
					title: "Redirect Application",
					description:
						"The redirect application is responsible for setting up 301 redirects and vanity URLs for all EMU pages.",
					buttonText: "Manage Redirects",
					buttonLink: "/redirects",
					display: false,
          category: 'Webmaster Tools'
				},
				programs: {
					title: "Degrees & Programs Manager",
					description:
						"The degrees and programs manager allows for the editing of program names, marketing website URLs, delivery modes, etc., that display at <a href=\"https://www.emich.edu/degrees\" target=\"_blank\">emich.edu/degrees</a>.",
					buttonText: "Manage Programs",
					buttonLink: "/programs",
					display: false,
          category: 'Academic Marketing'
				},
				directory: {
					// Added July 2025
					title: "Department Directory",
					description:
						"The department directory application manages all department information for the university directory at <a href=\"https://www.emich.edu/directory\" target=\"_blank\">emich.edu/directory</a>.",
					buttonText: "Manage Departments",
					buttonLink: "/directory",
					display: false,
          category: 'Maps & Directories'
				},
				photorequests: {
					// Added July 2025
					title: "Photo Requests",
					description:
						"The photo requests application handles photography and headshot requests that are submitted at <a href=\"https://www.emich.edu/photorequest\" target=\"_blank\">emich.edu/photorequest</a>.",
					buttonText: "See Requests",
					buttonLink: "/photorequests",
					display: false,
          category: 'Requests & Operations'
				},
				links: {
					// Added Sept. 2024
					title: "External Application Links",
					description:
						"A list of links to admin panels and front-ends for various external applications.",
					buttonText: "See Apps",
					buttonLink: "/applinks",
					display: true, // No permissions required for this module; it's just a list of links
          category: 'Webmaster Tools'
				},
        emergency: {
          // Added Sept. 2025
          title: "Emergency Banner and Notices",
          description:
              "The emergency banner application manages the emergency banner that displays above the header across all EMU websites.",
          buttonText: "Manage Banner",
          buttonLink: "/emergency",
          display: false,
          category: 'Campus Safety & Alerts'
        },
				crimelog: {
					// Added June 2025
					title: "DPS Crime Log",
					description:
						'This application allows DPS staff to upload the Daily Crime Log CSV file for display on <a href="https://www.emich.edu/police/crime-alerts-stats/daily-crime-log.php" target="_blank">emich.edu/police</a>.',
					buttonText: "DPS Crime Log Upload",
					buttonLink: "/crimelog",
					display: false,
          category: 'Campus Safety & Alerts'
				},
				cas: {
					// Added April 2026
					title: "CAS Application Links",
					description:
						"The CAS application links application manages the links to the CAS directory for all graduate programs.",
					buttonText: "Manage Links",
					buttonLink: "/cas",
					display: false,
					category: 'Academic Marketing'
				},
				social: {
					// Added July 2026
					title: "Social Media Links",
					description:
						"The social media links application manages Facebook, X, YouTube, Instagram, LinkedIn, and TikTok links for teams, groups, and other entities.",
					buttonText: "Manage Links",
					buttonLink: "/social-media",
					display: false,
					category: 'Academic Marketing'
				}
			}
		}
	},
	computed: {
    // Groups the displayed modules by category, in header order, dropping empty categories.
    visibleCategories: function() {
      let self = this
      return this.categoryOrder
        .map(function(category) {
          let modules = []
          for (let key in self.userModules) {
            if (self.userModules[key].category === category.name && self.userModules[key].display === true) {
              modules.push(self.userModules[key])
            }
          }
          return { name: category.name, mdDisplay: category.mdDisplay, modules: modules }
        })
        .filter(function(category) {
          return category.modules.length > 0
        })
    },
		// Does this user have permission to access any applications?
		userHasModules: function () {
			return this.visibleCategories.length > 0
		}
	},
	methods: {
		// Based on permissions passed to this component, enable module display for appropriate system applications
		registerUserModule: function (role) {
			if (role.includes("ROLE_MAP_") || role.includes("ROLE_GLOBAL_ADMIN")) {
				this.userModules.map.display = true
			}
			if (
				role.includes("ROLE_REDIRECT_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.redirect.display = true
			}
			if (
				role.includes("ROLE_PROGRAMS_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.programs.display = true
			}
			if (
				role.includes("ROLE_DIRECTORY_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.directory.display = true
			}
			if (
				role.includes("ROLE_CRIMELOG_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.crimelog.display = true
			}
			if (
				role.includes("ROLE_DEPARTMENTS_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.directory.display = true
			}
			if (
				role.includes("ROLE_PHOTO_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.photorequests.display = true
			}
			if (
				role.includes("ROLE_EMERGENCY_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.emergency.display = true
			}
			if (
				role.includes("ROLE_CAS_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.cas.display = true
			}
			if (
				role.includes("ROLE_SOCIAL_") ||
				role.includes("ROLE_GLOBAL_ADMIN")
			) {
				this.userModules.social.display = true
			}
		}
	},
}
</script>
<style lang="scss">
</style>
