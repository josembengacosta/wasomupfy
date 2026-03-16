// js/database.all.employees.js
if (typeof window.employeesData === "undefined") {
  window.employeesData = (function () {
    // Dados de exemplo
    const employees = Array.from({ length: 50 }, (_, i) => ({
      id: 1000 + i,
      avatar: i % 3 === 0 ? `https://i.pravatar.cc/150?img=${i % 50}` : null,
      account: `funcionario${i}`,
      name: `Funcionário ${i}`,
      username: `func${i}`,
      email: `func${i}@wasomupfy.com`,
      roles: getRandomRoles(),
      country: getRandomCountry(),
      city: getRandomCity(),
      status: getRandomStatus(),
      createdAt: getRandomDate(),
      phone: getRandomPhone(),
      plan: getRandomPlan(),
    }));

    function getRandomRoles() {
      const allRoles = ["admin", "distributor", "analyst", "financial"];
      const count = Math.floor(Math.random() * 3) + 1;
      const shuffled = allRoles.sort(() => 0.5 - Math.random());
      return shuffled.slice(0, count);
    }

    function getRandomCountry() {
      const countries = ["AO", "PT", "BR", "MZ"];
      return countries[Math.floor(Math.random() * countries.length)];
    }

    function getRandomCity() {
      const cities = {
        AO: ["Luanda", "Huambo", "Benguela", "Lubango"],
        PT: ["Lisboa", "Porto", "Coimbra", "Braga"],
        BR: ["São Paulo", "Rio de Janeiro", "Belo Horizonte", "Salvador"],
        MZ: ["Maputo", "Beira", "Nampula", "Quelimane"],
      };
      const country = getRandomCountry();
      const cityList = cities[country];
      return cityList[Math.floor(Math.random() * cityList.length)];
    }

    function getRandomStatus() {
      const statuses = ["active", "suspended", "review"];
      return statuses[Math.floor(Math.random() * statuses.length)];
    }

    function getRandomDate() {
      const date = new Date();
      date.setDate(date.getDate() - Math.floor(Math.random() * 365));
      return date.toISOString().split("T")[0];
    }

    function getRandomPhone() {
      const prefixes = ["+244", "+351", "+55", "+258"];
      return (
        prefixes[Math.floor(Math.random() * prefixes.length)] +
        " 9" +
        Math.floor(Math.random() * 90000000 + 10000000)
      );
    }

    function getRandomPlan() {
      const plans = ["Single", "Profissional", "Enterprise", "Admin"];
      return plans[Math.floor(Math.random() * plans.length)];
    }

    return {
      getEmployees: function () {
        return employees;
      },

      getEmployeeById: function (id) {
        return employees.find((emp) => emp.id === parseInt(id));
      },

      addEmployee: function (employeeData) {
        const newId = Math.max(...employees.map((e) => e.id)) + 1;
        const newEmployee = {
          id: newId,
          avatar: null,
          account: employeeData.account || `funcionario${newId}`,
          name: employeeData.name || "",
          username: employeeData.username || "",
          email: employeeData.email || "",
          roles: employeeData.roles || [],
          country: employeeData.country || "AO",
          city: employeeData.city || "",
          status: employeeData.status || "review",
          createdAt: new Date().toISOString().split("T")[0],
          phone: employeeData.phone || "",
          plan: employeeData.plan || "Single",
        };
        employees.push(newEmployee);
        return { success: true, id: newId, employee: newEmployee };
      },

      updateEmployee: function (id, employeeData) {
        const index = employees.findIndex((emp) => emp.id === parseInt(id));
        if (index !== -1) {
          employees[index] = { ...employees[index], ...employeeData };
          return { success: true };
        }
        return { success: false, error: "Funcionário não encontrado" };
      },

      deleteEmployee: function (id) {
        const index = employees.findIndex((emp) => emp.id === parseInt(id));
        if (index !== -1) {
          employees.splice(index, 1);
          return { success: true };
        }
        return { success: false, error: "Funcionário não encontrado" };
      },

      getEmployeeStats: function () {
        const stats = {
          total: employees.length,
          active: employees.filter((e) => e.status === "active").length,
          suspended: employees.filter((e) => e.status === "suspended").length,
          review: employees.filter((e) => e.status === "review").length,
          byCountry: {},
          byRole: {
            admin: 0,
            distributor: 0,
            analyst: 0,
            financial: 0,
          },
        };

        employees.forEach((emp) => {
          // Estatísticas por país
          stats.byCountry[emp.country] =
            (stats.byCountry[emp.country] || 0) + 1;

          // Estatísticas por função
          emp.roles.forEach((role) => {
            if (stats.byRole[role] !== undefined) {
              stats.byRole[role]++;
            }
          });
        });

        return stats;
      },
    };
  })();
}
