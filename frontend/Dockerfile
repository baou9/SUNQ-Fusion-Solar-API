FROM node:18-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:18-alpine
WORKDIR /app
ENV NODE_ENV=production
ENV PORT=3137
COPY --from=builder /app .
USER node
EXPOSE 3137
CMD ["npm", "start"]
