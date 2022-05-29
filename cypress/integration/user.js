describe("User", () => {
	before(() => {
		cy.viewport(1000, 1400);
	});

	it("can login as an admin and see the image dimensions", () => {
		cy.loginAsAdmin();
		cy.seeImageDimensions();
	});
});
