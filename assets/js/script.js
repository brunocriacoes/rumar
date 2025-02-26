// Utility functions

const Utils = {
  numbers({ count, min, max }) {
    const numbers = [];
    for (let i = 0; i < count; i++) {
      numbers.push(Math.floor(Math.random() * (max - min + 1)) + min);
    }
    return numbers;
  },
  rand(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  },
  months({ count }) {
    const months = [
      'January', 'February', 'March', 'April', 'May', 
      'June', 'July', 'August', 'September', 'October', 
      'November', 'December'
    ];
    return months.slice(0, count);
  },
  namedColor(index) {
    const colors = ['red', 'blue', 'green', 'purple', 'orange', 'yellow'];
    return colors[index % colors.length];
  },
  transparentize(color, opacity) {
    return `rgba(${getComputedStyle(document.documentElement).getPropertyValue(`--${color}`)}, ${opacity})`;
  }
};

// Configuração inicial do gráfico
const data = {
  labels: Utils.months({ count: 6 }),
  datasets: [
    {
      label: 'Dataset 1',
      backgroundColor: 'rgba(75, 192, 192, 0.5)',
      borderColor: 'rgb(75, 192, 192)',
      data: Utils.numbers({ count: 6, min: -100, max: 100 }),
    },
  ],
};

const config = {
  type: 'bar',
  data: data,
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: 'Chart.js Actions Example',
      },
    },
  },
};

// Criação do gráfico
const myChart = new Chart(
  document.getElementById('myChart'),
  config
);

// Ações
const actions = [
  {
    name: 'Randomize',
    handler(chart) {
      chart.data.datasets.forEach(dataset => {
        dataset.data = Utils.numbers({
          count: chart.data.labels.length,
          min: -100,
          max: 100,
        });
      });
      chart.update();
    },
  },
  {
    name: 'Add Dataset',
    handler(chart) {
      const data = chart.data;
      const dsColor = Utils.namedColor(chart.data.datasets.length);
      const newDataset = {
        label: 'Dataset ' + (data.datasets.length + 1),
        backgroundColor: dsColor,
        borderColor: dsColor,
        data: Utils.numbers({
          count: data.labels.length,
          min: -100,
          max: 100,
        }),
      };
      chart.data.datasets.push(newDataset);
      chart.update();
    },
  },
  {
    name: 'Add Data',
    handler(chart) {
      const data = chart.data;
      if (data.datasets.length > 0) {
        data.labels = Utils.months({
          count: data.labels.length + 1,
        });

        for (let index = 0; index < data.datasets.length; ++index) {
          data.datasets[index].data.push(Utils.rand(-100, 100));
        }

        chart.update();
      }
    },
  },
  {
    name: 'Remove Dataset',
    handler(chart) {
      chart.data.datasets.pop();
      chart.update();
    },
  },
  {
    name: 'Remove Data',
    handler(chart) {
      chart.data.labels.splice(-1, 1); // Remove o rótulo primeiro

      chart.data.datasets.forEach(dataset => {
        dataset.data.pop();
      });

      chart.update();
    },
  },
];

// Função para executar a ação
function executeAction(index) {
  actions[index].handler(myChart);
}