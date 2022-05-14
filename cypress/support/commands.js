Cypress.Commands.add("loginAsAdmin", () => {
	cy.visit("http://localhost:8888/wp-login.php").wait(500);
	cy.get("#user_login").type("admin");
	cy.get("#user_pass").type("password");
	cy.get("#wp-submit").click();
});

Cypress.Commands.add("seeImageDimensions", () => {
	cy.visit("http://localhost:8888/wp-admin/upload.php?mode=list").wait(500);
	cy.get(".dimensions").contains("303 × 53");
	cy.get(".filesize").contains("6.88 KB");
});
