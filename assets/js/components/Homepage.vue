<template>
	<div>
		<template v-if="typeof username != 'undefined'">
			<div class="row">
				<div class="col-xs-12">
					<h2>My Applications</h2>
				</div>
			</div>
			<div class="row">
				<template v-for="(module, i) in userModules">
					<div
						v-if="module.display"
						class="card col-sm-6 col-md-4"
						:key="'mod-' + i"
					>
						<div class="card-body">
							<h5 class="card-title">{{ module.title }}</h5>
							<p class="card-text"><span v-html="module.description"></span></p>
							<a :href="module.buttonLink" class="btn btn-primary">{{
								module.buttonText
							}}</a>
						</div>
					</div>
				</template>
				<p v-if="!userHasModules">
					You do not currently belong to any applications.
				</p>
			</div>
		</template>
		<template v-else>
			<p>Please <a href="login">sign in</a> to begin.</p>
		</template>
	</div>
</template>
<script>
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
	components: {},
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
			userModules: {
				map: {
					title: "Campus Map",
					description:
						"The campus map application contains all points of interest at EMU. These items are displayed at emich.edu/maps.",
					buttonText: "Open Application",
					buttonLink: "/map/items",
					display: false
				},
				// multimedia: {
				// 	title: "Multimedia Requests",
				// 	description:
				// 		"The photo requests application is a scheduler for photographer/videographer/graphic designer inquiries on campus.",
				// 	buttonText: "See Requests",
				// 	buttonLink: "/multimediarequests",
				// 	display: false
				// },
				redirect: {
					title: "Redirect Application",
					description:
						"The redirect application is responsible for setting up 301 redirects and vanity URLs for all EMU pages.",
					buttonText: "Manage Redirects",
					buttonLink: "/redirects",
					display: false
				},
				programs: {
					title: "Catalog Programs Manager",
					description:
						"The catalog programs manager allows for the editing of program names, websites, etc., from the Acalog course catalog.",
					buttonText: "Manage Programs",
					buttonLink: "/programs",
					display: false
				},
				directory: {
					// Added July 2025
					title: "Department Directory",
					description:
						"The department directory application manages all department information for the university directory.",
					buttonText: "Manage Departments",
					buttonLink: "/directory",
					display: false
				},
				links: {
					// Added Sept. 2024
					title: "External Application Links",
					description:
						"A list of links to admin panels and front-ends for various external applications.",
					buttonText: "See Apps",
					buttonLink: "/applinks",
					display: true // No permissions required for this module; it's just a list of links
				},
				crimelog: {
					// Added June 2025
					title: "DPS Crime Log",
					description:
						'This application allows DPS staff to upload the Daily Crime Log CSV file for display on the <a href="https://www.emich.edu/police/crime-alerts-stats/daily-crime-log.php" target="_blank">EMU Police website</a>.',
					buttonText: "DPS Crime Log Upload",
					buttonLink: "/crimelog",
					display: true
				}
			}
		}
	},
	computed: {
		// Does this user have permission to access any applications?
		userHasModules: function () {
			let hasModules = false
			for (let module in this.userModules) {
				if (this.userModules[module].display === true) {
					hasModules = true
				}
			}
			return hasModules
		}
	},
	methods: {
		// Based on permissions passed to this component, enable module display for appropriate system applications
		registerUserModule: function (role) {
			if (role.includes("ROLE_MAP_") || role.includes("ROLE_GLOBAL_ADMIN")) {
				this.userModules.map.display = true
			}
			// if (
			// 	role.includes("ROLE_MULTIMEDIA_") ||
			// 	role.includes("ROLE_GLOBAL_ADMIN")
			// ) {
			// 	this.userModules.multimedia.display = true
			// }
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
		}
	},
	filters: {},
	watch: {}
}
</script>
