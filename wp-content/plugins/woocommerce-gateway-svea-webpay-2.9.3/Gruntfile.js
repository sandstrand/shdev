module.exports = function(grunt) {
 
  grunt.initConfig({
 
    concat: {
      frontend: {
        src: [
          './assets/js/src/frontend/*.js'
        ],
        dest: './assets/js/frontend.min.js'
      },
      admin: {
        src: [
          './assets/js/src/backend/*.js'
        ],
        dest: './assets/js/backend.min.js'
      }
    },
    uglify: {
      options: {
        mangle: true
      },
      js: {
        files: {
          './assets/js/frontend.min.js': './assets/js/frontend.min.js',
          './assets/js/backend.min.js': './assets/js/backend.min.js'
        }
      }
    },
    sass: {
      development: {
        files: {
          "./assets/css/style.min.css": "./assets/sass/application.scss"
        },
        options: {
            style: "expanded"
        }
      },
      production: {
        files: {
          "./assets/css/style.min.css": "./assets/sass/application.scss"
        },
        options: {
            style: "compressed"
        }
      }
    },
    watch: {
      js: {
        files: [
          './assets/js/src/**/*.js'
          ],
        tasks: ['concat', 'uglify:js']
      },
      sass: {
        files: ['./assets/sass/**/*.scss'],
        tasks: ['sass:production'],
      }
    }
  });
 
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
 
  grunt.registerTask('default', ['watch']);
  grunt.registerTask('dist', ['concat', 'sass:development']);
};